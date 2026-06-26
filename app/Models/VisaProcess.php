<?php

namespace App\Models;

use App\Enums\VisaApplicationStatus;
use App\Enums\VisaIssuedStatus;
use App\Enums\VisaStageResult;
use App\ValueObjects\VisaStageDetails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class VisaProcess extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'visa_processes';

    protected $fillable = [
        'candidate_id',
        'visa_partner_id',
        // Interview & Trade Test
        'interview_date', 'interview_status', 'interview_completed', 'interview_remarks',
        'interview_details', 'interview_evidence_path',
        'trade_test_date', 'trade_test_status', 'trade_test_completed', 'trade_test_remarks',
        'trade_test_details',
        // Takamol Test
        'takamol_date', 'takamol_status', 'takamol_center', 'takamol_remarks',
        'takamol_appointment_slip_path', 'takamol_result_path',
        'takamol_details',
        // Medical/GAMCA
        'medical_date', 'medical_status', 'medical_completed', 'medical_center', 'medical_remarks',
        'medical_appointment_slip_path', 'medical_result_path',
        'medical_details',
        // E-Number
        'enumber', 'enumber_status', 'enumber_date',
        // Biometrics/Etimad
        'biometric_date', 'etimad_appointment_id', 'etimad_center', 'biometric_status', 'biometric_completed',
        'biometric_details',
        // Visa Documents Submission
        'visa_submission_date', 'visa_application_number', 'embassy',
        // Visa & PTN
        'visa_date', 'visa_number', 'visa_status', 'visa_issued',
        'visa_application_status', 'visa_issued_status', 'visa_application_details',
        'ptn_number', 'ptn_cleared', 'ptn_issue_date', 'ptn_document_path',
        // Protector Clearance (visa processing stage)
        'protector_clearance_date', 'protector_clearance_status', 'protector_clearance_remarks',
        'protector_submission_date', 'protector_performed', 'protector_document_path',
        // Ticket & Travel
        'ticket_uploaded', 'ticket_date', 'ticket_path', 'travel_plan_path',
        // General
        'overall_status', 'remarks',
        // Failure tracking
        'failed_at', 'failed_stage', 'failure_reason',
        // Audit
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'interview_date' => 'date',
        'trade_test_date' => 'date',
        'takamol_date' => 'date',
        'medical_date' => 'date',
        'biometric_date' => 'date',
        'enumber_date' => 'date',
        'visa_submission_date' => 'date',
        'visa_date' => 'date',
        'ticket_date' => 'date',
        'ptn_issue_date' => 'date',
        'protector_submission_date' => 'date',
        'protector_performed' => 'boolean',
        'failed_at' => 'datetime',
        'interview_completed' => 'boolean',
        'trade_test_completed' => 'boolean',
        'medical_completed' => 'boolean',
        'biometric_completed' => 'boolean',
        'visa_issued' => 'boolean',
        'ticket_uploaded' => 'boolean',
        'ptn_cleared' => 'boolean',
        // JSON detail columns
        'interview_details' => 'array',
        'trade_test_details' => 'array',
        'takamol_details' => 'array',
        'medical_details' => 'array',
        'biometric_details' => 'array',
        'visa_application_details' => 'array',
        // Enum casts
        'visa_application_status' => VisaApplicationStatus::class,
        'visa_issued_status' => VisaIssuedStatus::class,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * SECURITY: Hide sensitive visa and document information
     */
    protected $hidden = [
        'visa_number',
        'ticket_path',
        'ptn_number',
    ];

    /**
     * Visa processing stages configuration
     */
    const STAGES = [
        'initiated'       => ['label' => 'Initiated',             'order' => 1,  'color' => 'secondary'],
        'interview'       => ['label' => 'Interview',             'order' => 2,  'color' => 'info'],
        'trade_test'      => ['label' => 'Trade Test',            'order' => 3,  'color' => 'info'],
        'takamol'         => ['label' => 'Takamol Test',          'order' => 4,  'color' => 'info'],
        'medical'         => ['label' => 'Medical (GAMCA)',        'order' => 5,  'color' => 'info'],
        'enumber'         => ['label' => 'E-Number',              'order' => 6,  'color' => 'info'],
        'biometrics'      => ['label' => 'Biometrics (Etimad)',    'order' => 7,  'color' => 'info'],
        'visa_submission' => ['label' => 'Visa Submission',        'order' => 8,  'color' => 'warning'],
        'visa_issued'     => ['label' => 'Visa Issuance',          'order' => 9,  'color' => 'primary'],
        'ptn'             => ['label' => 'PTN Clearance',          'order' => 10, 'color' => 'primary'],
        'protector'       => ['label' => 'Protector Clearance',    'order' => 11, 'color' => 'warning'],
        'completed'       => ['label' => 'Completed',              'order' => 12, 'color' => 'success'],
    ];

    /**
     * Stages that support detailed tracking with appointments and evidence
     */
    const DETAIL_STAGES = ['interview', 'trade_test', 'takamol', 'medical', 'biometric', 'visa_application'];

    /**
     * Get all stages
     */
    public static function getStages()
    {
        return self::STAGES;
    }

    /**
     * Get current stage info
     */
    public function getCurrentStageInfo()
    {
        return self::STAGES[$this->overall_status] ?? self::STAGES['initiated'];
    }

    /**
     * Required-stage requirements that must be satisfied before a visa process
     * can be marked complete and the candidate handed over to the Departure
     * module. Returns a list of human-readable messages for any unmet
     * requirement; an empty array means the process is ready to complete.
     *
     * Note: Ticket & Travel Plan are intentionally NOT part of this list —
     * those are handled by the Departure module, not visa processing.
     */
    public function getOutstandingCompletionRequirements(): array
    {
        $errors = [];

        if ($this->interview_status !== 'passed') {
            $errors[] = 'Interview must be passed';
        }
        if ($this->medical_status !== 'fit') {
            $errors[] = 'Medical examination must be cleared as "fit"';
        }
        if ($this->biometric_status !== 'completed') {
            $errors[] = 'Biometrics must be completed';
        }
        if (empty($this->enumber) || $this->enumber_status !== 'verified') {
            $errors[] = 'E-Number must be generated and verified';
        }
        if ($this->visa_status !== 'issued') {
            $errors[] = 'Visa must be issued';
        }
        if (! $this->ptn_cleared) {
            $errors[] = 'PTN clearance must be confirmed';
        }
        if (! $this->protector_performed) {
            $errors[] = 'Protector clearance must be performed';
        }

        return $errors;
    }

    /**
     * Whether all required visa stages are complete and the process can be
     * marked complete (which transitions the candidate to the Departure module).
     */
    public function isReadyToComplete(): bool
    {
        return $this->overall_status !== 'completed'
            && empty($this->getOutstandingCompletionRequirements());
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute()
    {
        $currentOrder = $this->getCurrentStageInfo()['order'];
        $totalStages = count(self::STAGES) - 1; // Exclude completed
        return min(100, round(($currentOrder / $totalStages) * 100));
    }

    /**
     * Check if stage is completed
     */
    public function isStageCompleted($stage)
    {
        $currentOrder = $this->getCurrentStageInfo()['order'];
        $stageOrder = self::STAGES[$stage]['order'] ?? 0;
        return $currentOrder > $stageOrder;
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return $this->getCurrentStageInfo()['color'] ?? 'secondary';
    }

    /**
     * Boot method for automatic audit trail.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        // Clear the visa partner dashboard cache when visa stage data changes,
        // since that dashboard is driven entirely by VisaProcess fields.
        $clearVisaPartnerCache = function ($model) {
            if ($model->visa_partner_id) {
                \Illuminate\Support\Facades\Cache::forget('visa_partner_dashboard_' . $model->visa_partner_id);
            }
        };

        static::saved($clearVisaPartnerCache);
        static::deleted($clearVisaPartnerCache);
    }

    // =========================================================================
    // Stage Details Accessors (VisaStageDetails Value Objects)
    // =========================================================================

    public function getInterviewDetailsObjectAttribute(): VisaStageDetails
    {
        return VisaStageDetails::fromArray($this->interview_details);
    }

    public function getTradeTestDetailsObjectAttribute(): VisaStageDetails
    {
        return VisaStageDetails::fromArray($this->trade_test_details);
    }

    public function getTakamolDetailsObjectAttribute(): VisaStageDetails
    {
        return VisaStageDetails::fromArray($this->takamol_details);
    }

    public function getMedicalDetailsObjectAttribute(): VisaStageDetails
    {
        return VisaStageDetails::fromArray($this->medical_details);
    }

    public function getBiometricDetailsObjectAttribute(): VisaStageDetails
    {
        return VisaStageDetails::fromArray($this->biometric_details);
    }

    public function getVisaApplicationDetailsObjectAttribute(): VisaStageDetails
    {
        return VisaStageDetails::fromArray($this->visa_application_details);
    }

    // =========================================================================
    // Stage Overview & Hierarchical Status
    // =========================================================================

    /**
     * Get all stages with their details for hierarchical display
     */
    public function getStagesOverview(): array
    {
        return [
            'interview' => [
                'name' => 'Interview',
                'status' => $this->interview_status,
                'details' => $this->interview_details_object,
                'icon' => 'fas fa-user-tie',
            ],
            'trade_test' => [
                'name' => 'Trade Test',
                'status' => $this->trade_test_status,
                'details' => $this->trade_test_details_object,
                'icon' => 'fas fa-tools',
            ],
            'takamol' => [
                'name' => 'Takamol',
                'status' => $this->takamol_status,
                'details' => $this->takamol_details_object,
                'icon' => 'fas fa-certificate',
            ],
            'medical' => [
                'name' => 'Medical (GAMCA)',
                'status' => $this->medical_status,
                'details' => $this->medical_details_object,
                'icon' => 'fas fa-heartbeat',
            ],
            'biometric' => [
                'name' => 'Biometrics (Etimad)',
                'status' => $this->biometric_status,
                'details' => $this->biometric_details_object,
                'icon' => 'fas fa-fingerprint',
            ],
            'visa_application' => [
                'name' => 'Visa Application',
                'status' => $this->visa_application_status?->value,
                'details' => $this->visa_application_details_object,
                'icon' => 'fas fa-passport',
                'issued_status' => $this->visa_issued_status?->value,
            ],
        ];
    }

    /**
     * Get hierarchical status for dashboard categorization
     */
    public function getHierarchicalStatus(): array
    {
        $stages = $this->getStagesOverview();

        $scheduled = [];
        $done = [];
        $passed = [];
        $failed = [];
        $pending = [];

        foreach ($stages as $key => $stage) {
            $result = $stage['details']->getResultEnum();

            // For visa_application, also check the enum fields as fallback
            if ($key === 'visa_application' && !$result) {
                $issuedVal = $stage['issued_status'] ?? null;
                $statusVal = $stage['status'] ?? null;
                if ($issuedVal === 'confirmed') {
                    $passed[$key] = $stage;
                    continue;
                } elseif ($statusVal === 'refused' || $issuedVal === 'refused') {
                    $failed[$key] = $stage;
                    continue;
                } elseif ($statusVal === 'applied') {
                    $scheduled[$key] = $stage;
                    continue;
                }
            }

            if ($result === VisaStageResult::PASS) {
                $passed[$key] = $stage;
            } elseif ($result === VisaStageResult::FAIL || $result === VisaStageResult::REFUSED) {
                $failed[$key] = $stage;
            } elseif ($result === VisaStageResult::SCHEDULED || $stage['details']->isScheduled()) {
                $scheduled[$key] = $stage;
            } else {
                // Fall back to legacy status column values
                $status = $stage['status'] ?? null;
                if (in_array($status, ['passed', 'fit'])) {
                    $passed[$key] = $stage;
                } elseif (in_array($status, ['failed', 'unfit', 'refused'])) {
                    $failed[$key] = $stage;
                } elseif ($status === 'scheduled') {
                    $scheduled[$key] = $stage;
                } elseif ($status === 'completed') {
                    $done[$key] = $stage;
                } else {
                    $pending[$key] = $stage;
                }
            }
        }

        return [
            'scheduled' => $scheduled,
            'done' => $done,
            'passed' => $passed,
            'failed' => $failed,
            'pending' => $pending,
        ];
    }

    // =========================================================================
    // Stage Appointment & Result Management
    // =========================================================================

    /**
     * Schedule appointment for a stage
     */
    public function scheduleStageAppointment(string $stage, string $date, string $time, string $center): void
    {
        $detailsField = "{$stage}_details";
        $currentDetails = VisaStageDetails::fromArray($this->{$detailsField});

        $this->{$detailsField} = $currentDetails->withAppointment($date, $time, $center)->toArray();
        $this->{"{$stage}_status"} = 'scheduled';
        $this->save();

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties([
                'stage' => $stage,
                'appointment_date' => $date,
                'appointment_time' => $time,
                'center' => $center,
            ])
            ->log("Scheduled {$stage} appointment");
    }

    /**
     * Record result for a stage
     */
    public function recordStageResult(
        string $stage,
        string $resultStatus,
        ?string $notes = null,
        ?string $evidencePath = null
    ): void {
        $detailsField = "{$stage}_details";
        $currentDetails = VisaStageDetails::fromArray($this->{$detailsField});

        // Require evidence for pass/fail results
        if (in_array($resultStatus, ['pass', 'fail']) && !$evidencePath && !$currentDetails->hasEvidence()) {
            throw new \Exception("Evidence is required for {$resultStatus} result.");
        }

        $this->{$detailsField} = $currentDetails->withResult($resultStatus, $notes, $evidencePath)->toArray();

        // Map Module 5 result values to legacy-compatible status values per stage
        $legacyStatus = match ($stage) {
            'interview' => match ($resultStatus) {
                'pass' => 'passed',
                'fail', 'refused' => 'failed',
                default => $resultStatus,
            },
            'trade_test' => match ($resultStatus) {
                'pass' => 'passed',
                'fail', 'refused' => 'failed',
                default => $resultStatus,
            },
            'takamol' => match ($resultStatus) {
                'pass' => 'completed',
                'fail', 'refused' => 'failed',
                default => $resultStatus,
            },
            'medical' => match ($resultStatus) {
                'pass' => 'fit',
                'fail', 'refused' => 'unfit',
                default => $resultStatus,
            },
            'biometric' => match ($resultStatus) {
                'pass' => 'completed',
                'fail', 'refused' => 'failed',
                default => $resultStatus,
            },
            default => $resultStatus === 'pass' ? 'completed' : $resultStatus,
        };

        $this->{"{$stage}_status"} = $legacyStatus;

        // Set *_completed booleans for legacy compatibility
        if ($resultStatus === 'pass') {
            $completedFields = [
                'interview' => 'interview_completed',
                'trade_test' => 'trade_test_completed',
                'medical' => 'medical_completed',
                'biometric' => 'biometric_completed',
            ];
            if (isset($completedFields[$stage])) {
                $this->{$completedFields[$stage]} = true;
            }
        }

        // Advance overall_status to the next stage in the pipeline
        if ($resultStatus === 'pass') {
            $stageProgressMap = [
                'interview' => 'trade_test',
                'trade_test' => 'takamol',
                'takamol' => 'medical',
                'medical' => 'enumber',
                'biometric' => 'visa_submission',
            ];
            if (isset($stageProgressMap[$stage])) {
                $this->overall_status = $stageProgressMap[$stage];
            }
        } elseif (in_array($resultStatus, ['fail', 'refused'])) {
            // Keep overall_status at current stage on failure
            $this->overall_status = $stage;
        }

        $this->save();

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties([
                'stage' => $stage,
                'result' => $resultStatus,
            ])
            ->log("Recorded {$stage} result: {$resultStatus}");
    }

    /**
     * Upload evidence for a stage
     */
    public function uploadStageEvidence(string $stage, $file): string
    {
        $detailsField = "{$stage}_details";
        $currentDetails = VisaStageDetails::fromArray($this->{$detailsField});

        // Delete old evidence if exists
        if ($currentDetails->evidencePath) {
            Storage::disk('private')->delete($currentDetails->evidencePath);
        }

        $candidateId = $this->candidate_id;
        $timestamp = now()->format('Y-m-d_His');
        $extension = $file->getClientOriginalExtension();
        $filename = "visa_{$stage}_{$candidateId}_{$timestamp}.{$extension}";

        $path = $file->storeAs(
            "visa-process/{$candidateId}",
            $filename,
            'private'
        );

        // Update details with new evidence path
        $this->{$detailsField} = array_merge(
            $currentDetails->toArray(),
            ['evidence_path' => $path, 'updated_at' => now()->toDateTimeString()]
        );
        $this->save();

        return $path;
    }

    // =========================================================================
    // Backward Compatibility Stubs
    // =========================================================================

    /**
     * Set takamol booking date (ignored - not in database schema).
     * For backward compatibility with tests.
     */
    public function setTakamolBookingDateAttribute($value)
    {
        // Ignore - this field doesn't exist in the database schema
    }

    /**
     * Set GAMCA booking date (ignored - not in database schema).
     * For backward compatibility with tests.
     */
    public function setGamcaBookingDateAttribute($value)
    {
        // Ignore - this field doesn't exist in the database schema
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function visaPartner()
    {
        return $this->belongsTo(VisaPartner::class);
    }

    /**
     * Get the OEP through the candidate relationship.
     */
    public function oep()
    {
        return $this->hasOneThrough(
            Oep::class,
            Candidate::class,
            'id',           // candidates.id
            'id',           // oeps.id
            'candidate_id', // visa_processes.candidate_id
            'oep_id'        // candidates.oep_id
        );
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeSearch($query, $term)
    {
        // Escape special LIKE characters to prevent SQL LIKE injection
        $escapedTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return $query->where(function($q) use ($escapedTerm) {
            $q->where('overall_status', 'like', "%{$escapedTerm}%")
              ->orWhereHas('candidate', function($subQ) use ($escapedTerm) {
                  $subQ->where('name', 'like', "%{$escapedTerm}%")
                       ->orWhere('cnic', 'like', "%{$escapedTerm}%")
                       ->orWhere('btevta_id', 'like', "%{$escapedTerm}%");
              });
        });
    }
}
