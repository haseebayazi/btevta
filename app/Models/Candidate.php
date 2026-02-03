<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Carbon\Carbon;
use App\Enums\CandidateStatus;
use App\Observers\CandidateStatusObserver;

#[ObservedBy([CandidateStatusObserver::class])]
class Candidate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'candidates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'btevta_id',
        'name',
        'cnic',
        'phone',
        'email',
        'application_id',
        'batch_id',
        'campus_id',
        'program_id',
        'implementing_partner_id',
        'trade_id',
        'oep_id',
        'visa_partner_id',
        'district',
        'tehsil',
        'province',
        'date_of_birth',
        'gender',
        'father_name',
        'address',
        'emergency_contact',
        'blood_group',
        'marital_status',
        'qualification',
        'experience_years',
        'passport_number',
        'passport_expiry',
        'next_of_kin_id',
        'registration_date',
        'training_start_date',
        'training_end_date',
        'training_status',
        'status',
        'status_remarks',
        'photo_path',
        'remarks',
        'at_risk_reason',
        'at_risk_since',
        'screening_reminder_sent_at',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'passport_expiry' => 'date',
        'registration_date' => 'date',
        'training_start_date' => 'date',
        'training_end_date' => 'date',
        'experience_years' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_at' => 'datetime',
        'at_risk_since' => 'datetime',
        'screening_reminder_sent_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * SECURITY: Hide sensitive PII from API responses and serialization
     */
    protected $hidden = [
        'cnic',
        'passport_number',
        'emergency_contact',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'status' => 'new',
        'training_status' => 'pending',
    ];

    /**
     * ARCHITECTURE NOTE: Dual Status System
     * =====================================
     * The Candidate model uses TWO status fields:
     *
     * 1. `status` - Overall workflow stage (required)
     *    Flow: new → screening → registered → training → visa_process → ready → departed
     *    Terminal states: rejected, dropped, returned
     *    Use: Determines which module/dashboard view is appropriate
     *
     * 2. `training_status` - Sub-detail during training phase (only meaningful when status='training')
     *    Flow: not_started → in_progress → completed OR dropped
     *    Use: Tracks granular training progress (attendance/assessments)
     *
     * 3. `at_risk_reason` + `at_risk_since` - At-risk tracking (separate from status)
     *    These columns track candidates who are at-risk due to attendance or performance issues
     *    Clear these when candidate completes training successfully
     *
     * IMPORTANT: Never use 'at_risk' as a training_status value - use at_risk_reason column instead
     */

    /**
     * Status constants - Overall workflow stage
     */
    const STATUS_NEW = 'new';
    const STATUS_SCREENING = 'screening';
    const STATUS_REGISTERED = 'registered';
    const STATUS_TRAINING = 'training';
    const STATUS_VISA_PROCESS = 'visa_process';
    const STATUS_READY = 'ready';
    const STATUS_DEPARTED = 'departed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_DROPPED = 'dropped';
    const STATUS_RETURNED = 'returned';

    /**
     * Training status constants - Sub-detail during training phase
     * Note: Use these only when status='training'
     */
    const TRAINING_NOT_STARTED = 'not_started';
    const TRAINING_PENDING = 'pending';
    const TRAINING_IN_PROGRESS = 'in_progress';
    const TRAINING_COMPLETED = 'completed';
    const TRAINING_FAILED = 'failed';
    const TRAINING_DROPPED = 'dropped';

    /**
     * Get all possible status values
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_SCREENING => 'Screening',
            self::STATUS_REGISTERED => 'Registered',
            self::STATUS_TRAINING => 'Training',
            self::STATUS_VISA_PROCESS => 'Visa Process',
            self::STATUS_READY => 'Ready',
            self::STATUS_DEPARTED => 'Departed',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_DROPPED => 'Dropped',
            self::STATUS_RETURNED => 'Returned',
        ];
    }

    /**
     * Get all possible training status values
     */
    public static function getTrainingStatuses()
    {
        return [
            self::TRAINING_NOT_STARTED => 'Not Started',
            self::TRAINING_IN_PROGRESS => 'In Progress',
            self::TRAINING_COMPLETED => 'Completed',
            self::TRAINING_FAILED => 'Failed',
            self::TRAINING_DROPPED => 'Dropped',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the batch that the candidate belongs to.
     */
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the campus that the candidate belongs to.
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the trade that the candidate is associated with.
     */
    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    /**
     * Get the program that the candidate is enrolled in.
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the implementing partner assigned to the candidate.
     */
    public function implementingPartner()
    {
        return $this->belongsTo(ImplementingPartner::class);
    }

    /**
     * Get the OEP (Overseas Employment Promoter) assigned to the candidate.
     */
    public function oep()
    {
        return $this->belongsTo(Oep::class);
    }

    /**
     * Get the Visa Partner assigned to the candidate.
     */
    public function visaPartner()
    {
        return $this->belongsTo(VisaPartner::class);
    }

    /**
     * Get the next of kin for the candidate.
     */
    public function nextOfKin()
    {
        return $this->belongsTo(NextOfKin::class, 'next_of_kin_id');
    }

    /**
     * Get all screenings for the candidate.
     */
    public function screenings()
    {
        return $this->hasMany(CandidateScreening::class);
    }

    /**
     * Get the latest screening for the candidate.
     */
    public function latestScreening()
    {
        return $this->hasOne(CandidateScreening::class)->latest();
    }

    /**
     * Get all registration documents for the candidate.
     */
    public function registrationDocuments()
    {
        return $this->hasMany(RegistrationDocument::class);
    }

    /**
     * Get all undertakings for the candidate.
     */
    public function undertakings()
    {
        return $this->hasMany(Undertaking::class);
    }

    /**
     * Get all registration documents for the candidate.
     */
    public function documents()
    {
        return $this->hasMany(RegistrationDocument::class);
    }

    /**
     * Get all training attendances for the candidate.
     */
    public function trainingAttendances()
    {
        return $this->hasMany(TrainingAttendance::class);
    }

    /**
     * Get all training assessments for the candidate.
     */
    public function trainingAssessments()
    {
        return $this->hasMany(TrainingAssessment::class);
    }

    /**
     * Get all training certificates for the candidate.
     */
    public function trainingCertificates()
    {
        return $this->hasMany(TrainingCertificate::class);
    }

    /**
     * Get all training classes the candidate is enrolled in.
     */
    public function trainingClasses()
    {
        return $this->belongsToMany(TrainingClass::class, 'class_enrollments', 'candidate_id', 'training_class_id')
                    ->withPivot('enrollment_date', 'status', 'remarks', 'enrolled_by')
                    ->withTimestamps();
    }

    /**
     * Get the latest training certificate for the candidate.
     * Alias for trainingCertificates - returns the most recent certificate.
     */
    public function certificate()
    {
        return $this->hasOne(TrainingCertificate::class)->latest();
    }

    /**
     * Alias for trainingAttendances relationship.
     * Used by TrainingController for eager loading.
     */
    public function attendances()
    {
        return $this->trainingAttendances();
    }

    /**
     * Alias for trainingAssessments relationship.
     * Used by TrainingController for eager loading.
     */
    public function assessments()
    {
        return $this->trainingAssessments();
    }

    /**
     * Get the visa process for the candidate.
     */
    public function visaProcess()
    {
        return $this->hasOne(VisaProcess::class);
    }

    /**
     * Get the departure record for the candidate.
     */
    public function departure()
    {
        return $this->hasOne(Departure::class);
    }

    /**
     * Get all complaints filed by or about the candidate.
     */
    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * Get all correspondence related to the candidate.
     */
    public function correspondence()
    {
        return $this->hasMany(Correspondence::class);
    }

    /**
     * Get all remittances for the candidate.
     */
    public function remittances()
    {
        return $this->hasMany(Remittance::class);
    }

    /**
     * Get all beneficiaries for the candidate.
     */
    public function beneficiaries()
    {
        return $this->hasMany(RemittanceBeneficiary::class);
    }

    /**
     * Get the primary beneficiary for the candidate.
     */
    public function primaryBeneficiary()
    {
        return $this->hasOne(RemittanceBeneficiary::class)->where('is_primary', true);
    }

    /**
     * Get all remittance alerts for the candidate.
     */
    public function remittanceAlerts()
    {
        return $this->hasMany(RemittanceAlert::class);
    }

    /**
     * Get the user who created this candidate record.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this candidate record.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all pre-departure documents for this candidate
     */
    public function preDepartureDocuments()
    {
        return $this->hasMany(PreDepartureDocument::class);
    }

    /**
     * Get all licenses for this candidate
     */
    public function licenses()
    {
        return $this->hasMany(CandidateLicense::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to only include active candidates.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_NEW,
            self::STATUS_SCREENING,
            self::STATUS_REGISTERED,
            self::STATUS_TRAINING,
            self::STATUS_VISA_PROCESS,
            self::STATUS_READY
        ]);
    }

    /**
     * Scope a query to only include candidates in training.
     */
    public function scopeInTraining($query)
    {
        return $query->where('status', self::STATUS_TRAINING)
                     ->where('training_status', self::TRAINING_IN_PROGRESS);
    }

    /**
     * Scope a query to filter by district.
     */
    public function scopeByDistrict($query, $district)
    {
        return $query->where('district', $district);
    }

    /**
     * Scope a query to filter by campus.
     */
    public function scopeByCampus($query, $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Scope a query to filter by batch.
     */
    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope a query to filter by trade.
     */
    public function scopeByTrade($query, $tradeId)
    {
        return $query->where('trade_id', $tradeId);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to search by name, CNIC, or application ID.
     */
    public function scopeSearch($query, $search)
    {
        // Escape special LIKE characters to prevent SQL LIKE injection
        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);

        return $query->where(function ($q) use ($escapedSearch) {
            $q->where('name', 'like', "%{$escapedSearch}%")
              ->orWhere('cnic', 'like', "%{$escapedSearch}%")
              ->orWhere('application_id', 'like', "%{$escapedSearch}%")
              ->orWhere('phone', 'like', "%{$escapedSearch}%")
              ->orWhere('email', 'like', "%{$escapedSearch}%");
        });
    }

    /**
     * Scope to get candidates ready for departure.
     */
    public function scopeReadyForDeparture($query)
    {
        return $query->where('status', self::STATUS_READY)
                     ->whereHas('visaProcess', function ($q) {
                         $q->whereNotNull('visa_number')
                           ->whereNotNull('ticket_number');
                     });
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Get the candidate's full name.
     */
    public function getFullNameAttribute()
    {
        return $this->name;
    }

    /**
     * Get the candidate's age.
     */
    public function getAgeAttribute()
    {
        if ($this->date_of_birth) {
            return Carbon::parse($this->date_of_birth)->age;
        }
        return null;
    }

    /**
     * Get formatted CNIC.
     */
    public function getFormattedCnicAttribute()
    {
        if ($this->cnic && strlen($this->cnic) == 13) {
            return substr($this->cnic, 0, 5) . '-' .
                   substr($this->cnic, 5, 7) . '-' .
                   substr($this->cnic, 12, 1);
        }
        return $this->cnic;
    }

    /**
     * Get the photo URL.
     * Handles both external URLs and local storage paths.
     * Falls back to controller route if storage link doesn't exist.
     */
    public function getPhotoUrlAttribute()
    {
        if (empty($this->photo_path)) {
            return null;
        }

        // If it's already a full URL, return as-is
        if (str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://')) {
            return $this->photo_path;
        }

        // Check if storage link exists (storage/app/public symlinked to public/storage)
        $publicPath = public_path('storage/' . $this->photo_path);
        if (file_exists($publicPath)) {
            return asset('storage/' . $this->photo_path);
        }

        // Fallback: Serve through controller route (works even without storage link)
        return route('candidates.photo', $this);
    }

    /**
     * Get the candidate's status label.
     */
    public function getStatusLabelAttribute()
    {
        return self::getStatuses()[$this->status] ?? 'Unknown';
    }

    /**
     * Get the candidate's training status label.
     */
    public function getTrainingStatusLabelAttribute()
    {
        return self::getTrainingStatuses()[$this->training_status] ?? 'Unknown';
    }

    /**
     * Get days in training.
     */
    public function getDaysInTrainingAttribute()
    {
        if ($this->training_start_date) {
            $endDate = $this->training_end_date ?? now();
            return Carbon::parse($this->training_start_date)->diffInDays($endDate);
        }
        return 0;
    }

    /**
     * Check if candidate has completed all documents.
     */
    public function getHasCompleteDocumentsAttribute()
    {
        $requiredDocs = ['cnic', 'education', 'domicile', 'photo'];
        $uploadedDocs = $this->registrationDocuments()
                             ->whereIn('document_type', $requiredDocs)
                             ->pluck('document_type')
                             ->toArray();
        
        return count(array_diff($requiredDocs, $uploadedDocs)) === 0;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if candidate is eligible for training.
     */
    public function isEligibleForTraining()
    {
        return $this->status === self::STATUS_REGISTERED &&
               $this->screenings()->where('status', 'passed')->exists() &&
               $this->has_complete_documents;
    }

    /**
     * Update candidate status with validation.
     */
    public function updateStatus($newStatus, $remarks = null)
    {
        $allowedTransitions = [
            self::STATUS_NEW => [self::STATUS_SCREENING, self::STATUS_REJECTED],
            self::STATUS_SCREENING => [self::STATUS_REGISTERED, self::STATUS_REJECTED],
            self::STATUS_REGISTERED => [self::STATUS_TRAINING, self::STATUS_DROPPED],
            self::STATUS_TRAINING => [self::STATUS_VISA_PROCESS, self::STATUS_DROPPED],
            self::STATUS_VISA_PROCESS => [self::STATUS_READY, self::STATUS_REJECTED],
            self::STATUS_READY => [self::STATUS_DEPARTED],
            self::STATUS_DEPARTED => [self::STATUS_RETURNED],  // Workers can return from abroad
        ];

        $currentStatus = $this->status;
        
        if (!isset($allowedTransitions[$currentStatus]) || 
            !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            throw new \Exception("Invalid status transition from {$currentStatus} to {$newStatus}");
        }

        $this->status = $newStatus;
        if ($remarks !== null) {
            $this->status_remarks = $remarks;
        }
        
        // Handle special status transitions
        if ($newStatus === self::STATUS_TRAINING) {
            $this->training_status = self::TRAINING_IN_PROGRESS;
            $this->training_start_date = now();
        } elseif ($newStatus === self::STATUS_DEPARTED) {
            $this->training_status = self::TRAINING_COMPLETED;
            if (!$this->training_end_date) {
                $this->training_end_date = now();
            }
        }
        
        $this->save();
        
        // Log the status change
        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['old_status' => $currentStatus, 'new_status' => $newStatus])
            ->log('Candidate status updated');
        
        return true;
    }

    /**
     * Update training status.
     */
    public function updateTrainingStatus($status, $endDate = null)
    {
        if (!in_array($status, array_keys(self::getTrainingStatuses()))) {
            throw new \Exception("Invalid training status: {$status}");
        }

        $this->training_status = $status;

        if ($status === self::TRAINING_IN_PROGRESS && !$this->training_start_date) {
            $this->training_start_date = now();
        }

        if ($status === self::TRAINING_COMPLETED) {
            $this->training_end_date = $endDate ?? now();
        }

        return $this->save();
    }

    // ==================== PHASE 9: CROSS-PHASE TRANSITION VALIDATION ====================

    /**
     * Check if candidate can transition from NEW to SCREENING.
     * Validates that all required fields are filled.
     *
     * @return array ['can_transition' => bool, 'issues' => array]
     */
    public function canTransitionToScreening()
    {
        $issues = [];

        if ($this->status !== self::STATUS_NEW) {
            $issues[] = "Current status must be 'new'. Current: {$this->status}";
        }

        if (empty($this->name) || empty($this->cnic)) {
            $issues[] = 'Name and CNIC are required';
        }

        if (empty($this->phone)) {
            $issues[] = 'Phone number is required for call screening';
        }

        // NEW: Check pre-departure documents completion (Module 1 requirement)
        if (!$this->hasCompletedPreDepartureDocuments()) {
            $missingDocs = $this->getMissingMandatoryDocuments();
            $missingNames = $missingDocs->pluck('name')->toArray();
            $issues[] = 'All mandatory pre-departure documents must be uploaded before screening. Missing: ' . implode(', ', $missingNames);
        }

        return [
            'can_transition' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Check if candidate can transition from SCREENING to REGISTERED.
     * Validates that all required screenings are passed.
     *
     * @return array ['can_transition' => bool, 'issues' => array]
     */
    public function canTransitionToRegistered()
    {
        $issues = [];

        if ($this->status !== self::STATUS_SCREENING) {
            $issues[] = "Current status must be 'screening'. Current: {$this->status}";
        }

        // Check required screenings
        $requiredTypes = ['desk', 'call', 'physical'];
        $passedScreenings = $this->screenings()
            ->whereIn('screening_type', $requiredTypes)
            ->where('status', 'passed')
            ->pluck('screening_type')
            ->toArray();

        $missingScreenings = array_diff($requiredTypes, $passedScreenings);
        if (!empty($missingScreenings)) {
            $issues[] = 'Missing passed screenings: ' . implode(', ', $missingScreenings);
        }

        return [
            'can_transition' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Check if candidate can transition from REGISTERED to TRAINING.
     * Validates that registration documents and next of kin are complete.
     *
     * @return array ['can_transition' => bool, 'issues' => array]
     */
    public function canTransitionToTraining()
    {
        $issues = [];

        if ($this->status !== self::STATUS_REGISTERED) {
            $issues[] = "Current status must be 'registered'. Current: {$this->status}";
        }

        // Check required documents
        $requiredDocs = ['cnic', 'education', 'photo'];
        $uploadedDocs = $this->documents()
            ->whereIn('document_type', $requiredDocs)
            ->pluck('document_type')
            ->toArray();

        $missingDocs = array_diff($requiredDocs, $uploadedDocs);
        if (!empty($missingDocs)) {
            $issues[] = 'Missing documents: ' . implode(', ', $missingDocs);
        }

        // Check next of kin
        if (!$this->nextOfKin) {
            $issues[] = 'Next of kin information is required';
        }

        // Check undertaking
        if (!$this->undertakings()->where('is_completed', true)->exists()) {
            $issues[] = 'Signed undertaking is required';
        }

        return [
            'can_transition' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Check if candidate can transition from TRAINING to VISA_PROCESS.
     * Validates attendance, assessments, and certificate.
     *
     * @return array ['can_transition' => bool, 'issues' => array]
     */
    public function canTransitionToVisaProcess()
    {
        $issues = [];

        if ($this->status !== self::STATUS_TRAINING) {
            $issues[] = "Current status must be 'training'. Current: {$this->status}";
        }

        // Check training completion
        if ($this->training_status !== self::TRAINING_COMPLETED) {
            $issues[] = "Training must be completed. Current: {$this->training_status}";
        }

        // Check final assessment
        $finalAssessment = $this->trainingAssessments()
            ->where('assessment_type', 'final')
            ->where('result', 'pass')
            ->first();

        if (!$finalAssessment) {
            $issues[] = 'Final assessment must be passed';
        }

        // Check certificate
        if (!$this->certificate) {
            $issues[] = 'Training certificate must be issued';
        }

        return [
            'can_transition' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Check if candidate can transition from VISA_PROCESS to READY.
     * Validates visa and all required clearances.
     *
     * @return array ['can_transition' => bool, 'issues' => array]
     */
    public function canTransitionToReady()
    {
        $issues = [];

        if ($this->status !== self::STATUS_VISA_PROCESS) {
            $issues[] = "Current status must be 'visa_process'. Current: {$this->status}";
        }

        // Check visa process record
        $visaProcess = $this->visaProcess;
        if (!$visaProcess) {
            $issues[] = 'Visa process record not found';
        } else {
            if (!$visaProcess->visa_issued) {
                $issues[] = 'Visa must be issued';
            }
            if (!$visaProcess->trade_test_passed) {
                $issues[] = 'Trade test must be passed';
            }
            if (!$visaProcess->medical_passed) {
                $issues[] = 'Medical (GAMCA) must be passed';
            }
        }

        return [
            'can_transition' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Check if candidate can transition from READY to DEPARTED.
     * Validates departure record and pre-departure requirements.
     *
     * @return array ['can_transition' => bool, 'issues' => array]
     */
    public function canTransitionToDeparted()
    {
        $issues = [];

        if ($this->status !== self::STATUS_READY) {
            $issues[] = "Current status must be 'ready'. Current: {$this->status}";
        }

        // Check departure record
        $departure = $this->departure;
        if (!$departure) {
            $issues[] = 'Departure record not found';
        } else {
            if (!$departure->departure_date) {
                $issues[] = 'Departure date must be set';
            }
            if (!$departure->flight_number) {
                $issues[] = 'Flight details must be recorded';
            }
            if (!$departure->pre_briefing_completed) {
                $issues[] = 'Pre-departure briefing must be completed';
            }
        }

        return [
            'can_transition' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Validate transition and return detailed result.
     * Use this before calling updateStatus() to get specific failure reasons.
     *
     * @param string $targetStatus The status to transition to
     * @return array ['can_transition' => bool, 'issues' => array]
     */
    public function validateTransition($targetStatus)
    {
        $validators = [
            self::STATUS_SCREENING => 'canTransitionToScreening',
            self::STATUS_REGISTERED => 'canTransitionToRegistered',
            self::STATUS_TRAINING => 'canTransitionToTraining',
            self::STATUS_VISA_PROCESS => 'canTransitionToVisaProcess',
            self::STATUS_READY => 'canTransitionToReady',
            self::STATUS_DEPARTED => 'canTransitionToDeparted',
        ];

        if (isset($validators[$targetStatus])) {
            return $this->{$validators[$targetStatus]}();
        }

        // For rejected/dropped statuses, always allow
        if (in_array($targetStatus, [self::STATUS_REJECTED, self::STATUS_DROPPED])) {
            return ['can_transition' => true, 'issues' => []];
        }

        return ['can_transition' => false, 'issues' => ['Unknown target status: ' . $targetStatus]];
    }

    /**
     * Check if candidate can transition to a specific status.
     * Returns validation result with can_transition and issues.
     *
     * @param string $targetStatus
     * @return array ['can_transition' => bool, 'issues' => array]
     */
    public function canTransitionTo($targetStatus)
    {
        return $this->validateTransition($targetStatus);
    }

    /**
     * Get all allowed transitions from current status.
     *
     * @return array
     */
    public function getAllowedTransitions()
    {
        $allStatuses = [
            self::STATUS_SCREENING,
            self::STATUS_REGISTERED,
            self::STATUS_TRAINING,
            self::STATUS_VISA_PROCESS,
            self::STATUS_READY,
            self::STATUS_DEPARTED,
            self::STATUS_REJECTED,
            self::STATUS_DROPPED,
        ];

        $allowed = [];
        foreach ($allStatuses as $status) {
            if ($this->canTransitionTo($status)) {
                $allowed[] = $status;
            }
        }

        return $allowed;
    }

    // ==================== PRE-DEPARTURE DOCUMENT METHODS ====================

    /**
     * Check if candidate has completed all mandatory pre-departure documents
     */
    public function hasCompletedPreDepartureDocuments(): bool
    {
        $mandatoryDocuments = DocumentChecklist::mandatory()->active()->get();

        if ($mandatoryDocuments->isEmpty()) {
            // CRITICAL: If seeder hasn't been run, return false (not complete)
            // This prevents showing "100% complete" when no checklists exist
            return false;
        }

        $uploadedDocumentIds = $this->preDepartureDocuments()
            ->pluck('document_checklist_id')
            ->toArray();

        foreach ($mandatoryDocuments as $doc) {
            if (!in_array($doc->id, $uploadedDocumentIds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get pre-departure document completion status
     */
    public function getPreDepartureDocumentStatus(): array
    {
        $mandatory = DocumentChecklist::mandatory()->active()->get();
        $optional = DocumentChecklist::optional()->active()->get();

        $uploadedIds = $this->preDepartureDocuments()
            ->pluck('document_checklist_id')
            ->toArray();

        $mandatoryUploaded = $mandatory->filter(fn($doc) => in_array($doc->id, $uploadedIds))->count();
        $optionalUploaded = $optional->filter(fn($doc) => in_array($doc->id, $uploadedIds))->count();

        return [
            'mandatory_total' => $mandatory->count(),
            'mandatory_uploaded' => $mandatoryUploaded,
            'mandatory_complete' => $mandatoryUploaded >= $mandatory->count() && $mandatory->count() > 0,
            'optional_total' => $optional->count(),
            'optional_uploaded' => $optionalUploaded,
            'is_complete' => $this->hasCompletedPreDepartureDocuments(),
            'completion_percentage' => $mandatory->count() > 0
                ? round(($mandatoryUploaded / $mandatory->count()) * 100)
                : 0, // Return 0% when seeder hasn't been run, not 100%
            'seeder_required' => $mandatory->isEmpty() && $optional->isEmpty(),
        ];
    }

    /**
     * Get missing mandatory documents
     */
    public function getMissingMandatoryDocuments()
    {
        $mandatory = DocumentChecklist::mandatory()->active()->get();
        $uploadedIds = $this->preDepartureDocuments()
            ->pluck('document_checklist_id')
            ->toArray();

        return $mandatory->filter(fn($doc) => !in_array($doc->id, $uploadedIds));
    }

    /**
     * Get attendance percentage.
     */
    public function getAttendancePercentage($fromDate = null, $toDate = null)
    {
        $query = $this->trainingAttendances();
        
        if ($fromDate) {
            $query->where('date', '>=', $fromDate);
        }
        
        if ($toDate) {
            $query->where('date', '<=', $toDate);
        }
        
        $total = $query->count();
        
        if ($total === 0) {
            return 0;
        }
        
        $present = $query->where('status', 'present')->count();
        
        return round(($present / $total) * 100, 2);
    }

    /**
     * Get average assessment score.
     */
    public function getAverageAssessmentScore()
    {
        $avg = $this->trainingAssessments()->avg('score');
        return $avg ? round($avg, 2) : 0;
    }

    /**
     * Check if candidate passed all assessments.
     */
    public function hasPassedAllAssessments()
    {
        $assessments = $this->trainingAssessments;
        
        if ($assessments->isEmpty()) {
            return false;
        }
        
        return $assessments->every(function ($assessment) {
            return $assessment->score >= 60; // Assuming 60 is passing score
        });
    }

    /**
     * Get latest call screening.
     */
    public function getLatestCallScreening()
    {
        return $this->screenings()
                    ->where('screening_type', 'call')
                    ->latest()
                    ->first();
    }

    /**
     * Check if all screenings are complete.
     */
    public function hasCompletedScreening()
    {
        $requiredScreenings = ['desk', 'call', 'physical'];
        $completedScreenings = $this->screenings()
                                    ->whereIn('status', ['passed', 'failed'])
                                    ->pluck('screening_type')
                                    ->unique()
                                    ->toArray();
        
        return count(array_diff($requiredScreenings, $completedScreenings)) === 0;
    }
    /**
     * Check if candidate can record screening.
     */
    public function canRecordScreening(): bool
    {
        return in_array($this->status, [
            CandidateStatus::LISTED->value,
            CandidateStatus::PRE_DEPARTURE_DOCS->value,
            CandidateStatus::SCREENING->value,
        ]);
    }

    /**
     * Check if candidate can issue certificate.
     */
    public function canIssueCertificate(): bool
    {
        return in_array($this->status, [
            CandidateStatus::TRAINING_COMPLETED->value,
            CandidateStatus::VISA_PROCESS->value,
            CandidateStatus::VISA_APPROVED->value,
            CandidateStatus::DEPARTURE_PROCESSING->value,
            CandidateStatus::READY_TO_DEPART->value,
            CandidateStatus::DEPARTED->value,
            CandidateStatus::POST_DEPARTURE->value,
            CandidateStatus::COMPLETED->value,
        ]);
    }

    /**
     * Check if candidate can be reactivated.
     */
    public function canReactivate(): bool
    {
        return in_array($this->status, [
            CandidateStatus::DEFERRED->value,
            CandidateStatus::WITHDRAWN->value,
        ]);
    }

    /**
     * Check if candidate is in LISTED status.
     */
    public function isListed(): bool
    {
        return $this->status === CandidateStatus::LISTED->value;
    }

    /**
     * Get valid statuses for transition.
     */
    public static function getValidStatuses(): array
    {
        return array_column(CandidateStatus::cases(), 'value');
    }

    /**
     * Get candidate progress percentage.
     */
    public function getProgressPercentage(): float
    {
        $statusOrder = [
            CandidateStatus::LISTED->value => 5,
            CandidateStatus::PRE_DEPARTURE_DOCS->value => 10,
            CandidateStatus::SCREENING->value => 15,
            CandidateStatus::SCREENED->value => 20,
            CandidateStatus::REGISTERED->value => 25,
            CandidateStatus::TRAINING->value => 40,
            CandidateStatus::TRAINING_COMPLETED->value => 55,
            CandidateStatus::VISA_PROCESS->value => 65,
            CandidateStatus::VISA_APPROVED->value => 75,
            CandidateStatus::DEPARTURE_PROCESSING->value => 85,
            CandidateStatus::READY_TO_DEPART->value => 90,
            CandidateStatus::DEPARTED->value => 95,
            CandidateStatus::POST_DEPARTURE->value => 98,
            CandidateStatus::COMPLETED->value => 100,
            CandidateStatus::DEFERRED->value => 0,
            CandidateStatus::REJECTED->value => 0,
            CandidateStatus::WITHDRAWN->value => 0,
        ];

        return $statusOrder[$this->status] ?? 0;
    }


    /**
     * Assign to batch.
     */
    public function assignToBatch($batchId)
    {
        $batch = Batch::findOrFail($batchId);
        
        // Check batch capacity
        if ($batch->isFull()) {
            throw new \Exception("Batch is at full capacity");
        }
        
        $this->batch_id = $batchId;
        $this->save();
        
        return true;
    }

    /**
     * Generate a unique application ID.
     */
    public static function generateApplicationId()
    {
        $year = date('Y');
        $lastId = self::whereYear('created_at', $year)
                      ->max('application_id');

        if ($lastId) {
            $number = intval(substr($lastId, -6)) + 1;
        } else {
            $number = 1;
        }

        return 'APP' . $year . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique TheLeap ID for the candidate with Luhn check digit.
     * Format: TLP-YYYY-XXXXX-C (where C is check digit)
     */
    public static function generateBtevtaId()
    {
        $year = date('Y');
        $lastId = self::whereYear('created_at', $year)
                      ->where('btevta_id', 'like', 'TLP-' . $year . '-%')
                      ->max('btevta_id');

        if ($lastId) {
            // Extract the sequence number (before check digit if present)
            $parts = explode('-', $lastId);
            if (count($parts) >= 3) {
                $seqPart = $parts[2];
                // Remove check digit if present (format: XXXXX-C or XXXXXC)
                if (strlen($seqPart) > 5) {
                    $seqPart = substr($seqPart, 0, 5);
                }
                $number = intval($seqPart) + 1;
            } else {
                $number = 1;
            }
        } else {
            $number = 1;
        }

        $sequenceNum = str_pad($number, 5, '0', STR_PAD_LEFT);
        $baseId = $year . $sequenceNum; // e.g., "202500001"
        $checkDigit = self::calculateLuhnCheckDigit($baseId);

        return 'TLP-' . $year . '-' . $sequenceNum . '-' . $checkDigit;
    }

    /**
     * Calculate Luhn check digit for ID validation.
     *
     * @param string $number Numeric string to calculate check digit for
     * @return int Check digit (0-9)
     */
    public static function calculateLuhnCheckDigit($number)
    {
        $sum = 0;
        $length = strlen($number);
        // Luhn: double every other digit from the right (rightmost is position 1)
        for ($i = $length - 1, $pos = 1; $i >= 0; $i--, $pos++) {
            $digit = (int) $number[$i];
            if ($pos % 2 === 0) { // double every 2nd digit from the right
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }
        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Validate a TheLeap ID with its check digit.
     *
     * @param string $btevtaId Full TheLeap ID (e.g., TLP-2025-00001-7)
     * @return bool True if valid, false otherwise
     */
    public static function validateBtevtaId($btevtaId)
    {
        // Expected format: TLP-YYYY-XXXXX-C
        if (!preg_match('/^TLP-(\d{4})-(\d{5})-(\d)$/', $btevtaId, $matches)) {
            return false;
        }
        $year = $matches[1];
        $seq = $matches[2];
        $check = (int)$matches[3];
        $baseId = $year . $seq;
        $expectedCheck = self::calculateLuhnCheckDigit($baseId);
        return $check === $expectedCheck;
    }

    /**
     * Validate a Pakistani CNIC checksum (13 digits, custom algorithm)
     *
     * @param string $cnic
     * @return bool
     */
    public static function validateCnicChecksum($cnic)
    {
        if (!preg_match('/^\d{13}$/', $cnic)) {
            return false;
        }
        // Pakistani CNIC validation algorithm
        $weights = [1, 2, 3, 4, 5, 6, 7, 8, 9, 1, 2, 3];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cnic[$i] * $weights[$i];
        }
        $expectedCheckDigit = $sum % 11;
        if ($expectedCheckDigit === 10) {
            $expectedCheckDigit = 0;
        }
        $actualCheckDigit = (int) $cnic[12];
        return $expectedCheckDigit === $actualCheckDigit;
    }

    /**
     * Validate Pakistan phone number format.
     * Accepts: 03XX-XXXXXXX, 03XXXXXXXXX, +923XXXXXXXXX, 923XXXXXXXXX
     *
     * @param string $phone Phone number
     * @return bool True if valid Pakistan phone format
     */
    public static function validatePakistanPhone($phone)
    {
        // Remove spaces and dashes
        $phone = preg_replace('/[\s\-]/', '', $phone);

        // Valid patterns:
        // 1. 03XXXXXXXXX (11 digits starting with 03)
        // 2. +923XXXXXXXXX (13 chars starting with +92)
        // 3. 923XXXXXXXXX (12 digits starting with 92)

        $patterns = [
            '/^03[0-9]{9}$/',         // 03XX-XXXXXXX format
            '/^\+923[0-9]{9}$/',      // +923XX-XXXXXXX format
            '/^923[0-9]{9}$/',        // 923XX-XXXXXXX format
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $phone)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find potential duplicate candidates by phone, email, or similar name.
     * Returns candidates that might be duplicates for warning purposes.
     *
     * @param string|null $phone Phone number to check
     * @param string|null $email Email to check
     * @param string|null $name Name to check (for fuzzy matching)
     * @param int|null $excludeId Candidate ID to exclude from results
     * @return \Illuminate\Support\Collection Collection of potential duplicates
     */
    public static function findPotentialDuplicates($phone = null, $email = null, $name = null, $excludeId = null)
    {
        $duplicates = collect();

        $query = self::query();

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Check phone match
        if ($phone) {
            $normalizedPhone = preg_replace('/[\s\-]/', '', $phone);
            $phoneMatches = (clone $query)->where(function ($q) use ($phone, $normalizedPhone) {
                $q->where('phone', $phone)
                  ->orWhere('phone', $normalizedPhone)
                  ->orWhere('phone', 'like', '%' . substr($normalizedPhone, -10) . '%');
            })->get();

            foreach ($phoneMatches as $match) {
                $duplicates->push([
                    'candidate' => $match,
                    'match_type' => 'phone',
                    'confidence' => 95,
                ]);
            }
        }

        // Check email match
        if ($email) {
            $emailMatches = (clone $query)->where('email', $email)->get();

            foreach ($emailMatches as $match) {
                $duplicates->push([
                    'candidate' => $match,
                    'match_type' => 'email',
                    'confidence' => 100,
                ]);
            }
        }

        // Check similar name (using simple Levenshtein for performance)
        if ($name && strlen($name) >= 3) {
            $nameWords = explode(' ', strtolower(trim($name)));
            $firstWord = $nameWords[0] ?? '';

            if (strlen($firstWord) >= 3) {
                $nameMatches = (clone $query)
                    ->where('name', 'like', $firstWord . '%')
                    ->limit(10)
                    ->get();

                foreach ($nameMatches as $match) {
                    $similarity = 0;
                    similar_text(strtolower($name), strtolower($match->name), $similarity);

                    if ($similarity >= 80) {
                        $duplicates->push([
                            'candidate' => $match,
                            'match_type' => 'name',
                            'confidence' => round($similarity),
                        ]);
                    }
                }
            }
        }

        // Remove duplicates by candidate ID and keep highest confidence
        return $duplicates->groupBy(function ($item) {
            return $item['candidate']->id;
        })->map(function ($group) {
            return $group->sortByDesc('confidence')->first();
        })->values();
    }

    /**
     * Clear dashboard cache when candidate data changes.
     */
    private static function clearDashboardCache($candidate)
    {
        \Illuminate\Support\Facades\Cache::forget('dashboard_stats_all');
        \Illuminate\Support\Facades\Cache::forget('dashboard_alerts_all');

        if ($candidate->campus_id) {
            \Illuminate\Support\Facades\Cache::forget('dashboard_stats_' . $candidate->campus_id);
            \Illuminate\Support\Facades\Cache::forget('dashboard_alerts_' . $candidate->campus_id);
        }
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate IDs on creation
        static::creating(function ($candidate) {
            if (empty($candidate->btevta_id)) {
                $candidate->btevta_id = self::generateBtevtaId();
            }

            if (empty($candidate->application_id)) {
                $candidate->application_id = self::generateApplicationId();
            }

            if (auth()->check()) {
                $candidate->created_by = auth()->id();
            }
        });

        // Track who updated the record
        static::updating(function ($candidate) {
            if (auth()->check()) {
                $candidate->updated_by = auth()->id();
            }
        });

        // Clear dashboard cache on any candidate changes
        static::created(function ($candidate) {
            self::clearDashboardCache($candidate);
        });

        static::updated(function ($candidate) {
            self::clearDashboardCache($candidate);
        });

        static::deleted(function ($candidate) {
            self::clearDashboardCache($candidate);
        });
    }

    /**
     * Convert the model to an array for JSON.
     */
    public function toArray()
    {
        $array = parent::toArray();
        
        // Add computed attributes
        $array['full_name'] = $this->full_name;
        $array['age'] = $this->age;
        $array['formatted_cnic'] = $this->formatted_cnic;
        $array['status_label'] = $this->status_label;
        $array['training_status_label'] = $this->training_status_label;
        $array['days_in_training'] = $this->days_in_training;
        $array['has_complete_documents'] = $this->has_complete_documents;
        
        return $array;
    }
}
