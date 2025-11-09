<?php

namespace App\Services;

use App\Models\Departure;
use App\Models\Candidate;
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
     */
    public function recordPreDepartureBriefing($candidateId, $data)
    {
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
        $candidate = Candidate::find($candidateId);
        if (!$candidate) {
            throw new \Exception("Candidate not found with ID: {$candidateId}");
        }
        $candidate->update(['status' => 'pre_briefing_completed']);

        // Log activity
        activity()
            ->performedOn($departure)
            ->causedBy(auth()->user())
            ->log("Pre-departure briefing recorded");

        return $departure;
    }

    /**
     * Record departure
     */
    public function recordDeparture($candidateId, $data)
    {
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
        $candidate->update(['status' => 'departed']);

        // Log activity
        activity()
            ->performedOn($departure)
            ->causedBy(auth()->user())
            ->log("Departure recorded for {$data['departure_date']}");

        return $departure;
    }

    /**
     * Record Iqama number
     */
    public function recordIqama($departureId, $iqamaNumber, $issueDate = null, $expiryDate = null)
    {
        $departure = Departure::findOrFail($departureId);
        
        $departure->update([
            'iqama_number' => $iqamaNumber,
            'iqama_issue_date' => $issueDate ?? now(),
            'iqama_expiry_date' => $expiryDate,
            'current_stage' => 'iqama_issued',
        ]);

        // Update candidate status
        $departure->candidate->update(['status' => 'iqama_issued']);

        // Log activity
        activity()
            ->performedOn($departure)
            ->causedBy(auth()->user())
            ->log("Iqama number recorded: {$iqamaNumber}");

        return $departure;
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
        $logs[] = [
            'date' => $data['date'] ?? now()->toDateString(),
            'type' => $data['type'] ?? 'phone', // phone, email, whatsapp
            'contacted_by' => $data['contacted_by'] ?? auth()->user()->name,
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
        $complianceDeadline = $departureDate->addDays(90);
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
}