<?php

namespace App\Services;

use App\Models\Departure;
use App\Models\Candidate;
use App\Enums\CandidateStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DepartureService
{
    /**
     * Departure stages
     */
    const STAGES = [
        'pre_briefing' => 'Pre-Departure Briefing',
        'departed' => 'Departed',
        'iqama_issued' => 'Iqama Issued',
        'absher_registered' => 'Absher Registered',
        'qiwa_activated' => 'Qiwa ID Activated',
        'salary_confirmed' => 'Salary Confirmed',
        'compliance_verified' => '90-Day Compliance Verified',
    ];

    /**
     * Compliance status types
     */
    const COMPLIANCE_STATUS = [
        'pending' => 'Pending',
        'partial' => 'Partially Compliant',
        'compliant' => 'Compliant',
        'non_compliant' => 'Non-Compliant',
    ];

    /**
     * Get all departure stages
     */
    public function getStages(): array
    {
        return self::STAGES;
    }

    /**
     * Get compliance status types
     */
    public function getComplianceStatus()
    {
        return self::COMPLIANCE_STATUS;
    }

    /**
     * Record pre-departure briefing
     * AUDIT FIX: Wrapped in DB transaction for atomicity
     */
    public function recordPreDepartureBriefing($candidateId, $data)
    {
        return DB::transaction(function () use ($candidateId, $data) {
            $departure = Departure::firstOrCreate(
                ['candidate_id' => $candidateId],
                [
                    'pre_briefing_date' => $data['briefing_date'],
                    'pre_briefing_conducted_by' => $data['conducted_by'] ?? auth()->id(),
                    'briefing_topics' => $data['topics'] ?? null,
                    'briefing_remarks' => $data['remarks'] ?? null,
                    'current_stage' => 'pre_briefing',
                ]
            );

            if (!$departure->wasRecentlyCreated) {
                $departure->update([
                    'pre_briefing_date' => $data['briefing_date'],
                    'pre_briefing_conducted_by' => $data['conducted_by'] ?? auth()->id(),
                    'briefing_topics' => $data['topics'] ?? null,
                    'briefing_remarks' => $data['remarks'] ?? null,
                ]);
            }

            // Update candidate status with NULL CHECK
            // Pre-departure briefing is a sub-step while candidate is in 'ready' status
            $candidate = Candidate::find($candidateId);
            if (!$candidate) {
                throw new \Exception("Candidate not found with ID: {$candidateId}");
            }
            // Only update to 'ready' if not already departed
            if ($candidate->status !== CandidateStatus::DEPARTED->value) {
                $candidate->update(['status' => CandidateStatus::READY->value]);
            }

            // Log activity
            activity()
                ->performedOn($departure)
                ->causedBy(auth()->user())
                ->log("Pre-departure briefing recorded");

            return $departure;
        });
    }

    /**
     * Record departure
     * AUDIT FIX: Wrapped in DB transaction for atomicity
     */
    public function recordDeparture($candidateId, $data)
    {
        return DB::transaction(function () use ($candidateId, $data) {
            $departure = Departure::firstOrCreate(
                ['candidate_id' => $candidateId]
            );

            $departure->update([
                'departure_date' => $data['departure_date'],
                'flight_number' => $data['flight_number'] ?? null,
                'airport' => $data['airport'] ?? null,
                'destination' => $data['destination'] ?? 'Saudi Arabia',
                'country_code' => $data['country_code'] ?? 'SA',
                'departure_remarks' => $data['remarks'] ?? null,
                'current_stage' => 'departed',
            ]);

            // Update candidate status with NULL CHECK
            $candidate = Candidate::find($candidateId);
            if (!$candidate) {
                throw new \Exception("Candidate not found with ID: {$candidateId}");
            }
            $candidate->update(['status' => CandidateStatus::DEPARTED->value]);

            // Log activity
            activity()
                ->performedOn($departure)
                ->causedBy(auth()->user())
                ->log("Departure recorded for {$data['departure_date']}");

            return $departure;
        });
    }

    /**
     * Record Iqama number
     * AUDIT FIX: Wrapped in DB transaction for atomicity
     */
    public function recordIqama($departureId, $iqamaNumber, $issueDate = null, $expiryDate = null)
    {
        return DB::transaction(function () use ($departureId, $iqamaNumber, $issueDate, $expiryDate) {
            $departure = Departure::findOrFail($departureId);

            $departure->update([
                'iqama_number' => $iqamaNumber,
                'iqama_issue_date' => $issueDate ?? now(),
                'iqama_expiry_date' => $expiryDate,
                'current_stage' => 'iqama_issued',
            ]);

            // Update candidate status with NULL CHECK
            if (!$departure->candidate) {
                throw new \Exception("Departure {$departureId} has no associated candidate");
            }
            $departure->candidate->update(['status' => 'iqama_issued']);

            // Log activity
            activity()
                ->performedOn($departure)
                ->causedBy(auth()->user())
                ->log("Iqama number recorded: {$iqamaNumber}");

            return $departure;
        });
    }

    /**
     * Upload medical report (post-arrival)
     */
    public function uploadMedicalReport($departureId, $file, $reportDate = null)
    {
        $departure = Departure::findOrFail($departureId);
        
        // Store file
        $path = $file->store('departure/medical', 'public');
        
        $departure->update([
            'medical_report_path' => $path,
            'medical_report_date' => $reportDate ?? now(),
        ]);

        // Log activity
        activity()
            ->performedOn($departure)
            ->causedBy(auth()->user())
            ->log("Post-arrival medical report uploaded");

        return $departure;
    }

    /**
     * Record Absher registration
     */
    public function recordAbsherRegistration($departureId, $data)
    {
        $departure = Departure::findOrFail($departureId);
        
        $departure->update([
            'absher_registered' => true,
            'absher_registration_date' => $data['registration_date'] ?? now(),
            'absher_id' => $data['absher_id'] ?? null,
            'absher_verification_status' => $data['verification_status'] ?? 'verified',
            'current_stage' => 'absher_registered',
        ]);

        // Update candidate status
        $departure->candidate->update(['status' => 'absher_registered']);

        // Log activity
        activity()
            ->performedOn($departure)
            ->causedBy(auth()->user())
            ->log("Absher registration recorded");

        return $departure;
    }

    /**
     * Record Qiwa ID activation
     */
    public function recordQiwaActivation($departureId, $data)
    {
        $departure = Departure::findOrFail($departureId);
        
        $departure->update([
            'qiwa_id' => $data['qiwa_id'],
            'qiwa_activation_date' => $data['activation_date'] ?? now(),
            'qiwa_status' => $data['status'] ?? 'active',
            'current_stage' => 'qiwa_activated',
        ]);

        // Update candidate status
        $departure->candidate->update(['status' => 'qiwa_activated']);

        // Log activity
        activity()
            ->performedOn($departure)
            ->causedBy(auth()->user())
            ->log("Qiwa ID activation recorded");

        return $departure;
    }

    /**
     * Record salary confirmation
     */
    public function recordSalaryConfirmation($departureId, $data)
    {
        $departure = Departure::findOrFail($departureId);
        
        $departure->update([
            'salary_amount' => $data['salary_amount'],
            'salary_currency' => $data['salary_currency'] ?? 'SAR',
            'first_salary_date' => $data['first_salary_date'] ?? now(),
            'salary_confirmed' => true,
            'salary_confirmation_date' => now(),
            'salary_remarks' => $data['remarks'] ?? null,
            'current_stage' => 'salary_confirmed',
        ]);

        // Update candidate status
        $departure->candidate->update(['status' => 'salary_confirmed']);

        // Log activity
        activity()
            ->performedOn($departure)
            ->causedBy(auth()->user())
            ->log("Salary confirmation recorded: {$data['salary_amount']} {$data['salary_currency']}");

        return $departure;
    }

    /**
     * Record accommodation status
     */
    public function recordAccommodation($departureId, $data)
    {
        $departure = Departure::findOrFail($departureId);
        
        $departure->update([
            'accommodation_type' => $data['accommodation_type'] ?? 'employer_provided',
            'accommodation_address' => $data['accommodation_address'] ?? null,
            'accommodation_status' => $data['accommodation_status'] ?? 'verified',
            'accommodation_verified_date' => now(),
            'accommodation_remarks' => $data['remarks'] ?? null,
        ]);

        // Log activity
        activity()
            ->performedOn($departure)
            ->causedBy(auth()->user())
            ->log("Accommodation status updated");

        return $departure;
    }

    /**
     * Record employer contact
     */
    public function recordEmployerContact($departureId, $data)
    {
        $departure = Departure::findOrFail($departureId);
        
        $departure->update([
            'employer_name' => $data['employer_name'],
            'employer_contact' => $data['employer_contact'],
            'employer_address' => $data['employer_address'] ?? null,
            'employer_id_number' => $data['employer_id_number'] ?? null,
        ]);

        return $departure;
    }

    /**
     * Add post-departure communication log
     */
    public function addCommunicationLog($departureId, $data)
    {
        $departure = Departure::findOrFail($departureId);
        
        // Get existing logs
        $logs = $departure->communication_logs ? json_decode($departure->communication_logs, true) : [];
        
        // Add new log
        // AUDIT FIX: Use null-safe operator to prevent null reference exception
        $logs[] = [
            'date' => $data['date'] ?? now()->toDateString(),
            'type' => $data['type'] ?? 'phone', // phone, email, whatsapp
            'contacted_by' => $data['contacted_by'] ?? auth()->user()?->name ?? 'System',
            'summary' => $data['summary'],
            'issues_reported' => $data['issues_reported'] ?? null,
            'follow_up_required' => $data['follow_up_required'] ?? false,
        ];
        
        $departure->update([
            'communication_logs' => json_encode($logs),
            'last_contact_date' => $data['date'] ?? now(),
        ]);

        return $departure;
    }

    /**
     * Check 90-day compliance
     */
    public function check90DayCompliance($departureId)
    {
        $departure = Departure::with('candidate')->findOrFail($departureId);
        
        if (!$departure->departure_date) {
            return [
                'status' => 'pending',
                'message' => 'Departure date not recorded',
                'days_since_departure' => 0,
                'compliance_items' => [],
            ];
        }

        $departureDate = Carbon::parse($departure->departure_date);
        $daysSinceDeparture = $departureDate->diffInDays(now());
        // AUDIT FIX: Use copy() to prevent mutating the original $departureDate
        // Without copy(), addDays() modifies $departureDate in place causing bugs
        $complianceDeadline = $departureDate->copy()->addDays(90);
        $daysRemaining = Carbon::now()->diffInDays($complianceDeadline, false);

        // Check compliance items
        $complianceItems = [
            'iqama_issued' => [
                'status' => !empty($departure->iqama_number),
                'label' => 'Iqama Number',
                'value' => $departure->iqama_number ?? 'Not recorded',
            ],
            'absher_registered' => [
                'status' => $departure->absher_registered ?? false,
                'label' => 'Absher Registration',
                'value' => $departure->absher_registered ? 'Registered' : 'Not registered',
            ],
            'qiwa_activated' => [
                'status' => !empty($departure->qiwa_id),
                'label' => 'Qiwa ID',
                'value' => $departure->qiwa_id ?? 'Not activated',
            ],
            'salary_confirmed' => [
                'status' => $departure->salary_confirmed ?? false,
                'label' => 'Salary Confirmation',
                'value' => $departure->salary_confirmed ? 'Confirmed' : 'Not confirmed',
            ],
            'accommodation_verified' => [
                'status' => $departure->accommodation_status === 'verified',
                'label' => 'Accommodation',
                'value' => $departure->accommodation_status ?? 'Not verified',
            ],
        ];

        $totalItems = count($complianceItems);
        $completedItems = count(array_filter($complianceItems, function($item) {
            return $item['status'];
        }));

        // Determine overall compliance status
        $compliancePercentage = ($completedItems / $totalItems) * 100;
        
        if ($compliancePercentage === 100) {
            $complianceStatus = 'compliant';
            $departure->update([
                'current_stage' => 'compliance_verified',
                'compliance_verified_date' => now(),
            ]);
        } elseif ($compliancePercentage >= 80) {
            $complianceStatus = 'partial';
        } elseif ($daysSinceDeparture > 90) {
            $complianceStatus = 'non_compliant';
        } else {
            $complianceStatus = 'pending';
        }

        return [
            'status' => $complianceStatus,
            'days_since_departure' => $daysSinceDeparture,
            'days_remaining' => max(0, $daysRemaining),
            'compliance_percentage' => $compliancePercentage,
            'completed_items' => $completedItems,
            'total_items' => $totalItems,
            'compliance_items' => $complianceItems,
            'is_overdue' => $daysRemaining < 0,
        ];
    }

    /**
     * Get 90-day compliance report
     */
    public function get90DayComplianceReport($filters = [])
    {
        $query = Departure::with(['candidate.oep', 'candidate.trade', 'candidate.campus'])
            ->whereNotNull('departure_date');

        // Apply filters
        if (!empty($filters['oep_id'])) {
            $query->whereHas('candidate', function($q) use ($filters) {
                $q->where('oep_id', $filters['oep_id']);
            });
        }

        if (!empty($filters['campus_id'])) {
            $query->whereHas('candidate', function($q) use ($filters) {
                $q->where('campus_id', $filters['campus_id']);
            });
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('departure_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('departure_date', '<=', $filters['to_date']);
        }

        $departures = $query->get();

        $report = [
            'total' => $departures->count(),
            'compliant' => 0,
            'partial' => 0,
            'non_compliant' => 0,
            'pending' => 0,
            'details' => [],
        ];

        foreach ($departures as $departure) {
            $compliance = $this->check90DayCompliance($departure->id);
            
            $report[$compliance['status']]++;
            
            $report['details'][] = [
                'departure' => $departure,
                'candidate' => $departure->candidate,
                'compliance' => $compliance,
            ];
        }

        return $report;
    }

    /**
     * Get pending compliance items
     */
    public function getPendingComplianceItems($filters = [])
    {
        $query = Departure::with(['candidate.oep', 'candidate.trade'])
            ->whereNotNull('departure_date')
            ->where(function($q) {
                $q->whereNull('iqama_number')
                  ->orWhere('absher_registered', false)
                  ->orWhereNull('qiwa_id')
                  ->orWhere('salary_confirmed', false)
                  ->orWhere('accommodation_status', '!=', 'verified');
            });

        if (!empty($filters['oep_id'])) {
            $query->whereHas('candidate', function($q) use ($filters) {
                $q->where('oep_id', $filters['oep_id']);
            });
        }

        $departures = $query->get();

        return $departures->map(function($departure) {
            $compliance = $this->check90DayCompliance($departure->id);
            
            $pendingItems = array_filter($compliance['compliance_items'], function($item) {
                return !$item['status'];
            });

            return [
                'departure' => $departure,
                'candidate' => $departure->candidate,
                'days_since_departure' => $compliance['days_since_departure'],
                'days_remaining' => $compliance['days_remaining'],
                'pending_items' => $pendingItems,
                'is_overdue' => $compliance['is_overdue'],
            ];
        });
    }

    /**
     * Get departure statistics
     */
    public function getStatistics($filters = [])
    {
        $query = Departure::with('candidate');

        // Apply filters
        if (!empty($filters['from_date'])) {
            $query->whereDate('departure_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('departure_date', '<=', $filters['to_date']);
        }

        if (!empty($filters['oep_id'])) {
            $query->whereHas('candidate', function($q) use ($filters) {
                $q->where('oep_id', $filters['oep_id']);
            });
        }

        $departures = $query->get();
        $total = $departures->count();

        return [
            'total_departures' => $total,
            'iqama_issued' => $departures->whereNotNull('iqama_number')->count(),
            'absher_registered' => $departures->where('absher_registered', true)->count(),
            'qiwa_activated' => $departures->whereNotNull('qiwa_id')->count(),
            'salary_confirmed' => $departures->where('salary_confirmed', true)->count(),
            'accommodation_verified' => $departures->where('accommodation_status', 'verified')->count(),
            'compliance_rate' => $total > 0 ? round(($departures->where('current_stage', 'compliance_verified')->count() / $total) * 100, 2) : 0,
            'by_stage' => $this->groupByStage($departures),
        ];
    }

    /**
     * Group departures by current stage
     */
    private function groupByStage($departures)
    {
        return $departures->groupBy('current_stage')->map(function($group, $stage) {
            return [
                'stage' => self::STAGES[$stage] ?? $stage,
                'count' => $group->count(),
            ];
        });
    }

    /**
     * Get departure list with filters
     */
    public function getDepartureList($filters = [])
    {
        $query = Departure::with(['candidate.oep', 'candidate.trade', 'candidate.campus']);

        // Apply filters
        if (!empty($filters['from_date'])) {
            $query->whereDate('departure_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('departure_date', '<=', $filters['to_date']);
        }

        if (!empty($filters['oep_id'])) {
            $query->whereHas('candidate', function($q) use ($filters) {
                $q->where('oep_id', $filters['oep_id']);
            });
        }

        if (!empty($filters['trade_id'])) {
            $query->whereHas('candidate', function($q) use ($filters) {
                $q->where('trade_id', $filters['trade_id']);
            });
        }

        if (!empty($filters['current_stage'])) {
            $query->where('current_stage', $filters['current_stage']);
        }

        return $query->orderBy('departure_date', 'desc')->get();
    }

    /**
     * Send compliance reminder
     */
    public function sendComplianceReminder($departureId)
    {
        $departure = Departure::with('candidate')->findOrFail($departureId);
        $compliance = $this->check90DayCompliance($departureId);

        if ($compliance['status'] === 'compliant') {
            return [
                'success' => false,
                'message' => 'Candidate is already compliant',
            ];
        }

        // Here you would integrate with notification service
        // For now, just log the activity
        activity()
            ->performedOn($departure)
            ->causedBy(auth()->user())
            ->log("Compliance reminder sent - {$compliance['days_remaining']} days remaining");

        return [
            'success' => true,
            'message' => 'Compliance reminder sent successfully',
            'compliance' => $compliance,
        ];
    }

    /**
     * Record Iqama details (controller compatibility wrapper)
     */
    public function recordIqamaDetails($candidateId, $iqamaNumber, $issueDate, $expiryDate, $medicalPath = null)
    {
        $departure = Departure::firstOrCreate(['candidate_id' => $candidateId]);

        $departure->update([
            'iqama_number' => $iqamaNumber,
            'iqama_issue_date' => $issueDate,
            'iqama_expiry_date' => $expiryDate,
            'post_arrival_medical_path' => $medicalPath,
            'current_stage' => 'iqama_issued',
        ]);

        if ($departure->candidate) {
            $departure->candidate->update(['status' => 'iqama_issued']);
        }

        activity()
            ->performedOn($departure)
            ->causedBy(auth()->user())
            ->log("Iqama details recorded: {$iqamaNumber}");

        return $departure;
    }

    /**
     * Record WPS/QIWA registration (alias for recordQiwaActivation)
     */
    public function recordWPSRegistration($candidateId, $registrationDate, $wpsId, $remarks = null)
    {
        $departure = Departure::firstOrCreate(['candidate_id' => $candidateId]);

        return $this->recordQiwaActivation($departure->id, [
            'qiwa_id' => $wpsId,
            'activation_date' => $registrationDate,
            'status' => 'active',
        ]);
    }

    /**
     * Record first salary (controller compatibility wrapper)
     */
    public function recordFirstSalary($candidateId, $salaryDate, $amount, $proofPath = null)
    {
        $departure = Departure::firstOrCreate(['candidate_id' => $candidateId]);

        $data = [
            'salary_amount' => $amount,
            'first_salary_date' => $salaryDate,
            'salary_currency' => 'SAR',
        ];

        if ($proofPath) {
            $departure->update(['salary_proof_path' => $proofPath]);
        }

        return $this->recordSalaryConfirmation($departure->id, $data);
    }

    /**
     * Record 90-day compliance
     */
    /**
     * Record 90-day compliance status for a deployed candidate.
     * PHASE 6 FIX: Updates deployment remarks instead of non-existent compliance_status field.
     */
    public function record90DayCompliance($candidateId, $complianceDate, $isCompliant, $remarks = null)
    {
        $departure = Departure::firstOrCreate(['candidate_id' => $candidateId]);

        $departure->update([
            'ninety_day_report_submitted' => $isCompliant,
            'compliance_verified_date' => $complianceDate,
            'compliance_remarks' => $remarks,
            'current_stage' => $isCompliant ? 'compliance_verified' : $departure->current_stage,
        ]);

        // PHASE 6 FIX: Update candidate remarks instead of non-existent compliance_status field
        if ($departure->candidate) {
            $complianceNote = $isCompliant
                ? "[Compliant] 90-day report verified on {$complianceDate}"
                : "[Non-Compliant] 90-day report issues on {$complianceDate}";

            if ($remarks) {
                $complianceNote .= ": {$remarks}";
            }

            $currentRemarks = $departure->candidate->remarks ?? '';
            $departure->candidate->update([
                'remarks' => $currentRemarks . ($currentRemarks ? "\n" : '') . $complianceNote,
            ]);
        }

        activity()
            ->performedOn($departure)
            ->causedBy(auth()->user())
            ->withProperties([
                'is_compliant' => $isCompliant,
                'compliance_date' => $complianceDate,
            ])
            ->log("90-day compliance recorded: " . ($isCompliant ? 'Compliant' : 'Non-compliant'));

        return $departure;
    }

    /**
     * Report post-departure issue.
     * PHASE 6 FIX: Uses UUID for secure ID generation instead of uniqid().
     */
    public function reportIssue($candidateId, $issueType, $issueDate, $description, $severity, $evidencePath = null)
    {
        DB::beginTransaction();
        try {
            // This would typically be a separate DepartureIssue model
            // For now, log it in the departure record
            $departure = Departure::firstOrCreate(['candidate_id' => $candidateId]);

            $issues = $departure->issues ? json_decode($departure->issues, true) : [];

            // PHASE 6 FIX: Use UUID for secure, unique ID generation
            $issue = [
                'id' => 'issue_' . \Illuminate\Support\Str::uuid()->toString(),
                'type' => $issueType,
                'date' => $issueDate,
                'description' => $description,
                'severity' => $severity,
                'evidence_path' => $evidencePath,
                'status' => 'open',
                'reported_by' => auth()->id(),
                'reported_at' => now()->toDateTimeString(),
            ];

            $issues[] = $issue;

            $departure->update(['issues' => json_encode($issues)]);

            activity()
                ->performedOn($departure)
                ->causedBy(auth()->user())
                ->withProperties([
                    'issue_id' => $issue['id'],
                    'issue_type' => $issueType,
                    'severity' => $severity,
                ])
                ->log("Issue reported: {$issueType} - {$severity}");

            DB::commit();
            return $issue;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update issue status.
     * PHASE 6 FIX: Uses indexed JSON search instead of loading all departures (N+1 query fix).
     */
    public function updateIssueStatus($issueId, $status, $resolutionNotes = null)
    {
        // PHASE 6 FIX: Use direct database search instead of loading all records
        // Search for departure containing this issue ID using JSON search
        $departure = Departure::whereNotNull('issues')
            ->where('issues', 'LIKE', '%' . str_replace(['%', '_'], ['\\%', '\\_'], $issueId) . '%')
            ->first();

        if (!$departure) {
            throw new \Exception("Issue not found: {$issueId}");
        }

        $issues = json_decode($departure->issues, true);
        $issueFound = false;

        foreach ($issues as $key => $issue) {
            if ($issue['id'] === $issueId) {
                $issues[$key]['status'] = $status;
                $issues[$key]['resolution_notes'] = $resolutionNotes;
                $issues[$key]['resolved_by'] = auth()->id();
                $issues[$key]['resolved_at'] = now()->toDateTimeString();
                $issueFound = true;

                $departure->update(['issues' => json_encode($issues)]);

                activity()
                    ->performedOn($departure)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'issue_id' => $issueId,
                        'new_status' => $status,
                    ])
                    ->log("Issue {$issueId} updated to: {$status}");

                return $issues[$key];
            }
        }

        throw new \Exception("Issue not found in departure record: {$issueId}");
    }

    /**
     * Get departure timeline
     */
    public function getDepartureTimeline($candidateId)
    {
        $departure = Departure::with('candidate')->where('candidate_id', $candidateId)->firstOrFail();

        $timeline = [];

        if ($departure->pre_briefing_date) {
            $timeline[] = [
                'stage' => 'Pre-Departure Briefing',
                'date' => $departure->pre_briefing_date,
                'status' => 'completed',
            ];
        }

        if ($departure->departure_date) {
            $timeline[] = [
                'stage' => 'Departure',
                'date' => $departure->departure_date,
                'status' => 'completed',
                'details' => $departure->flight_number,
            ];
        }

        if ($departure->iqama_issue_date) {
            $timeline[] = [
                'stage' => 'Iqama Issued',
                'date' => $departure->iqama_issue_date,
                'status' => 'completed',
            ];
        }

        if ($departure->absher_registration_date) {
            $timeline[] = [
                'stage' => 'Absher Registered',
                'date' => $departure->absher_registration_date,
                'status' => 'completed',
            ];
        }

        if ($departure->qiwa_activation_date) {
            $timeline[] = [
                'stage' => 'Qiwa/WPS Activated',
                'date' => $departure->qiwa_activation_date,
                'status' => 'completed',
            ];
        }

        if ($departure->first_salary_date) {
            $timeline[] = [
                'stage' => 'First Salary',
                'date' => $departure->first_salary_date,
                'status' => 'completed',
            ];
        }

        if ($departure->compliance_verified_date) {
            $timeline[] = [
                'stage' => '90-Day Compliance',
                'date' => $departure->compliance_verified_date,
                'status' => 'completed',
            ];
        }

        return collect($timeline)->sortBy('date')->values()->all();
    }

    /**
     * Generate compliance report (wrapper for get90DayComplianceReport)
     */
    public function generateComplianceReport($startDate, $endDate, $oepId = null)
    {
        $filters = [
            'from_date' => $startDate,
            'to_date' => $endDate,
        ];

        if ($oepId) {
            $filters['oep_id'] = $oepId;
        }

        return $this->get90DayComplianceReport($filters);
    }

    /**
     * Get 90-day tracking (wrapper for get90DayComplianceReport)
     */
    public function get90DayTracking()
    {
        $filters = [
            'from_date' => now()->subDays(90)->toDateString(),
            'to_date' => now()->toDateString(),
        ];

        return $this->get90DayComplianceReport($filters);
    }

    /**
     * Get non-compliant candidates
     */
    public function getNonCompliantCandidates()
    {
        $departures = Departure::with(['candidate.oep', 'candidate.trade'])
            ->whereNotNull('departure_date')
            ->get();

        $nonCompliant = [];

        foreach ($departures as $departure) {
            $departureDate = Carbon::parse($departure->departure_date);
            $daysSinceDeparture = $departureDate->diffInDays(now());

            if ($daysSinceDeparture > 90) {
                $compliance = $this->check90DayCompliance($departure->id);

                if ($compliance['status'] === 'non_compliant') {
                    $nonCompliant[] = [
                        'departure' => $departure,
                        'candidate' => $departure->candidate,
                        'compliance' => $compliance,
                    ];
                }
            }
        }

        return collect($nonCompliant);
    }

    /**
     * Get active issues
     */
    public function getActiveIssues()
    {
        $departures = Departure::whereNotNull('issues')->get();

        $activeIssues = [];

        foreach ($departures as $departure) {
            $issues = json_decode($departure->issues, true);

            foreach ($issues as $issue) {
                if (in_array($issue['status'], ['open', 'investigating'])) {
                    $activeIssues[] = [
                        'issue' => $issue,
                        'departure' => $departure,
                        'candidate' => $departure->candidate,
                    ];
                }
            }
        }

        return collect($activeIssues)->sortByDesc('issue.date')->values();
    }

    /**
     * Mark candidate as returned
     */
    public function markAsReturned($candidateId, $returnDate, $returnReason, $remarks = null)
    {
        DB::beginTransaction();
        try {
            $departure = Departure::where('candidate_id', $candidateId)->firstOrFail();

            $departure->update([
                'return_date' => $returnDate,
                'return_reason' => $returnReason,
                'return_remarks' => $remarks,
                'current_stage' => 'returned',
            ]);

            activity()
                ->performedOn($departure)
                ->causedBy(auth()->user())
                ->log("Candidate marked as returned: {$returnReason}");

            DB::commit();
            return $departure;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get compliance checklist
     */
    public function getComplianceChecklist($candidateId)
    {
        $departure = Departure::where('candidate_id', $candidateId)->first();

        if (!$departure) {
            return [
                'items' => [],
                'completed' => 0,
                'total' => 0,
                'percentage' => 0,
            ];
        }

        $items = [
            ['name' => 'Iqama Issued', 'completed' => !empty($departure->iqama_number)],
            ['name' => 'Absher Registered', 'completed' => $departure->absher_registered ?? false],
            ['name' => 'Qiwa/WPS Activated', 'completed' => !empty($departure->qiwa_id)],
            ['name' => 'First Salary Confirmed', 'completed' => $departure->salary_confirmed ?? false],
            ['name' => 'Accommodation Verified', 'completed' => $departure->accommodation_status === 'verified'],
        ];

        $completed = count(array_filter($items, fn($item) => $item['completed']));
        $total = count($items);

        return [
            'items' => $items,
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ];
    }
}