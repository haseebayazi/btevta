<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class CandidateScreening extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'candidate_screenings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'candidate_id',
        'screening_type',
        'screening_stage',
        'screening_date',
        'screener_name',
        'contact_method',
        'status',
        'outcome',
        'remarks',
        'next_steps',
        'screened_by',
        'screened_at',
        'completed_at',
        'evidence_path',
        'call_count',
        'call_duration',
        'next_call_date',
        'verification_status',
        'verification_remarks',
        'created_by',
        'updated_by',
        // Module 2: Initial Screening fields
        'consent_for_work',
        'placement_interest',
        'target_country_id',
        'screening_status',
        'reviewer_id',
        'reviewed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'screening_date' => 'date',
        'screened_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_call_date' => 'datetime',
        'call_duration' => 'integer',
        'call_count' => 'integer',
        'screening_stage' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_at' => 'datetime',
        // Module 2: Initial Screening casts
        'consent_for_work' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'status' => 'pending',
        'call_count' => 0,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * SECURITY: Hide evidence file paths to prevent unauthorized access
     */
    protected $hidden = [
        'evidence_path',
    ];

    /**
     * Screening type constants
     */
    const TYPE_DESK = 'desk';
    const TYPE_CALL = 'call';
    const TYPE_PHYSICAL = 'physical';
    const TYPE_DOCUMENT = 'document';
    const TYPE_MEDICAL = 'medical';

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PASSED = 'passed';
    const STATUS_FAILED = 'failed';
    const STATUS_DEFERRED = 'deferred';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Module 2: Initial Screening outcome constants
     */
    const OUTCOME_PENDING = 'pending';
    const OUTCOME_SCREENED = 'screened';
    const OUTCOME_DEFERRED = 'deferred';

    /**
     * Maximum call attempts
     */
    const MAX_CALL_ATTEMPTS = 3;

    /**
     * Get all screening types
     */
    public static function getScreeningTypes()
    {
        return [
            self::TYPE_DESK => 'Desk Screening',
            self::TYPE_CALL => 'Call Screening',
            self::TYPE_PHYSICAL => 'Physical Screening',
            self::TYPE_DOCUMENT => 'Document Verification',
            self::TYPE_MEDICAL => 'Medical Screening',
        ];
    }

    /**
     * Get all status types
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_PASSED => 'Passed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_DEFERRED => 'Deferred',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the candidate being screened.
     */
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Get the user who performed the screening.
     */
    public function screener()
    {
        return $this->belongsTo(User::class, 'screened_by');
    }

    /**
     * Get the undertaking associated with this screening's candidate.
     * Note: Undertakings are linked to candidates, not directly to screenings.
     */
    public function undertaking()
    {
        return $this->hasOneThrough(
            Undertaking::class,
            Candidate::class,
            'id',           // Foreign key on candidates table
            'candidate_id', // Foreign key on undertakings table
            'candidate_id', // Local key on candidate_screenings table
            'id'            // Local key on candidates table
        );
    }

    /**
     * Get the user who created this record.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the target country for international placement.
     * Module 2: Initial Screening
     */
    public function targetCountry()
    {
        return $this->belongsTo(Country::class, 'target_country_id');
    }

    /**
     * Get the reviewer who conducted the initial screening.
     * Module 2: Initial Screening
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to get pending screenings.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get passed screenings.
     */
    public function scopePassed($query)
    {
        return $query->where('status', self::STATUS_PASSED);
    }

    /**
     * Scope to get failed screenings.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get screenings by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('screening_type', $type);
    }

    /**
     * Scope to get today's screenings.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('screened_at', Carbon::today());
    }

    /**
     * Scope to get overdue call screenings.
     */
    public function scopeOverdueCallScreenings($query)
    {
        return $query->where('screening_type', self::TYPE_CALL)
                     ->where('status', self::STATUS_PENDING)
                     ->where('next_call_date', '<', now())
                     ->where('call_count', '<', self::MAX_CALL_ATTEMPTS);
    }

    /**
     * Scope to get screenings requiring follow-up.
     */
    public function scopeRequiringFollowUp($query)
    {
        return $query->whereIn('status', [self::STATUS_DEFERRED, self::STATUS_IN_PROGRESS])
                     ->orWhere(function ($q) {
                         $q->where('screening_type', self::TYPE_CALL)
                           ->where('call_count', '<', self::MAX_CALL_ATTEMPTS)
                           ->where('status', '!=', self::STATUS_PASSED);
                     });
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Get screening type label.
     */
    public function getScreeningTypeLabelAttribute()
    {
        return self::getScreeningTypes()[$this->screening_type] ?? 'Unknown';
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute()
    {
        return self::getStatuses()[$this->status] ?? 'Unknown';
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_PASSED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_DEFERRED => 'secondary',
            self::STATUS_CANCELLED => 'dark',
        ];
        
        return $colors[$this->status] ?? 'light';
    }

    /**
     * Get call attempt display.
     */
    public function getCallAttemptDisplayAttribute()
    {
        if ($this->screening_type !== self::TYPE_CALL) {
            return null;
        }
        
        return $this->call_count . '/' . self::MAX_CALL_ATTEMPTS;
    }

    /**
     * Check if maximum call attempts reached.
     */
    public function getMaxCallsReachedAttribute()
    {
        return $this->screening_type === self::TYPE_CALL && 
               $this->call_count >= self::MAX_CALL_ATTEMPTS;
    }

    /**
     * Get formatted call duration.
     */
    public function getFormattedCallDurationAttribute()
    {
        if (!$this->call_duration) {
            return null;
        }
        
        $minutes = floor($this->call_duration / 60);
        $seconds = $this->call_duration % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Increment call count and update next call date.
     * @deprecated Use markAsScreened() for Module 2 initial screening workflow
     */
    public function incrementCallCount()
    {
        if ($this->screening_type !== self::TYPE_CALL) {
            throw new \Exception('Can only increment call count for call screenings');
        }
        
        if ($this->call_count >= self::MAX_CALL_ATTEMPTS) {
            throw new \Exception('Maximum call attempts already reached');
        }
        
        $this->call_count++;
        
        // Set next call date to tomorrow if not at max attempts
        if ($this->call_count < self::MAX_CALL_ATTEMPTS) {
            $this->next_call_date = Carbon::tomorrow();
        } else {
            $this->next_call_date = null;
        }
        
        $this->save();
        
        return $this->call_count;
    }

    /**
     * Module 2: Mark screening as screened (approved).
     * This method is for the new single-review workflow.
     */
    public function markAsScreened($notes = null)
    {
        $this->screening_status = self::OUTCOME_SCREENED;
        $this->reviewer_id = auth()->id();
        $this->reviewed_at = now();

        if ($notes) {
            $this->remarks = $notes;
        }

        $this->save();

        // Update candidate status to 'screened'
        $this->candidate->update(['status' => \App\Enums\CandidateStatus::SCREENED->value]);

        // Log activity
        activity()
            ->performedOn($this->candidate)
            ->causedBy(auth()->user())
            ->withProperties([
                'screening_status' => self::OUTCOME_SCREENED,
                'placement_interest' => $this->placement_interest,
                'target_country' => $this->targetCountry?->name,
            ])
            ->log('Candidate marked as screened');

        return true;
    }

    /**
     * Module 2: Mark screening as deferred.
     * This method is for the new single-review workflow.
     */
    public function markAsDeferred($reason)
    {
        $this->screening_status = self::OUTCOME_DEFERRED;
        $this->reviewer_id = auth()->id();
        $this->reviewed_at = now();
        $this->remarks = $reason;

        $this->save();

        // Update candidate status to 'deferred'
        $this->candidate->update(['status' => \App\Enums\CandidateStatus::DEFERRED->value]);

        // Log activity
        activity()
            ->performedOn($this->candidate)
            ->causedBy(auth()->user())
            ->withProperties([
                'screening_status' => self::OUTCOME_DEFERRED,
                'reason' => $reason,
            ])
            ->log('Candidate screening deferred');

        return true;
    }

    /**
     * Check if all required calls have been completed.
     */
    public function hasCompletedRequiredCalls()
    {
        return $this->screening_type === self::TYPE_CALL && 
               ($this->status === self::STATUS_PASSED || 
                $this->call_count >= self::MAX_CALL_ATTEMPTS);
    }

    /**
     * Mark screening as passed.
     */
    public function markAsPassed($remarks = null)
    {
        $this->status = self::STATUS_PASSED;
        $this->screened_at = now();
        $this->screened_by = auth()->id();
        
        if ($remarks) {
            $this->remarks = $remarks;
        }
        
        $this->save();
        
        // Update candidate status if all screenings passed
        $this->checkAndUpdateCandidateStatus();
        
        return true;
    }

    /**
     * Mark screening as failed.
     */
    public function markAsFailed($remarks = null)
    {
        $this->status = self::STATUS_FAILED;
        $this->screened_at = now();
        $this->screened_by = auth()->id();
        
        if ($remarks) {
            $this->remarks = $remarks;
        }
        
        $this->save();
        
        // Update candidate status
        $this->candidate->updateStatus('rejected', 'Failed ' . $this->screening_type_label);
        
        return true;
    }

    /**
     * Defer screening for later.
     */
    public function defer($nextDate, $remarks = null)
    {
        $this->status = self::STATUS_DEFERRED;
        $this->next_call_date = $nextDate;
        
        if ($remarks) {
            $this->remarks = $remarks;
        }
        
        $this->save();
        
        return true;
    }

    /**
     * Record a call attempt.
     */
    public function recordCallAttempt($duration = null, $answered = false, $remarks = null)
    {
        if ($this->screening_type !== self::TYPE_CALL) {
            throw new \Exception('Can only record call attempts for call screenings');
        }
        
        $this->incrementCallCount();
        
        if ($duration) {
            $this->call_duration = $duration;
        }
        
        if (!$answered) {
            $this->status = self::STATUS_IN_PROGRESS;
            $remarks = ($remarks ?? '') . ' [Call not answered]';
        }
        
        if ($remarks) {
            $this->remarks = trim($this->remarks . "\n" . $remarks);
        }
        
        $this->save();
        
        return true;
    }

    /**
     * Allowed file extensions for evidence uploads.
     */
    const ALLOWED_EVIDENCE_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

    /**
     * Maximum file size for evidence uploads (5MB).
     */
    const MAX_EVIDENCE_SIZE = 5 * 1024 * 1024;

    /**
     * Upload and store evidence file with security validation.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string The stored file path
     * @throws \Exception If file validation fails
     */
    public function uploadEvidence($file)
    {
        if (!$file || !$file->isValid()) {
            throw new \Exception('Invalid file provided');
        }

        // Validate file extension (security: prevent executable uploads)
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EVIDENCE_EXTENSIONS)) {
            throw new \Exception(
                'Invalid file type. Allowed types: ' . implode(', ', self::ALLOWED_EVIDENCE_EXTENSIONS)
            );
        }

        // Validate MIME type matches extension (prevent extension spoofing)
        $mimeType = $file->getMimeType();
        $validMimes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        if (isset($validMimes[$extension]) && !str_contains($mimeType, explode('/', $validMimes[$extension])[0])) {
            throw new \Exception('File type mismatch. The file content does not match its extension.');
        }

        // Validate file size
        if ($file->getSize() > self::MAX_EVIDENCE_SIZE) {
            throw new \Exception('File too large. Maximum size is 5MB.');
        }

        // Delete old evidence if exists
        if ($this->evidence_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($this->evidence_path);
        }

        $candidateId = $this->candidate_id;
        $screeningType = $this->screening_type;
        $timestamp = now()->format('Y-m-d_His');

        $filename = "screening_{$candidateId}_{$screeningType}_{$timestamp}.{$extension}";

        $path = $file->storeAs('screenings/evidence', $filename, 'public');

        $this->evidence_path = $path;
        $this->save();

        activity()
            ->performedOn($this)
            ->log('Evidence file uploaded');

        return $path;
    }

    /**
     * Get the evidence URL for secure access.
     */
    public function getEvidenceUrlAttribute()
    {
        if (empty($this->evidence_path)) {
            return null;
        }
        return route('secure-file.view', ['path' => $this->evidence_path]);
    }

    /**
     * Get screening progress summary for a candidate.
     * Returns the status of each required screening type.
     *
     * @param Candidate $candidate
     * @return array
     */
    public static function getScreeningProgress(Candidate $candidate)
    {
        $requiredTypes = [self::TYPE_DESK, self::TYPE_CALL, self::TYPE_PHYSICAL];

        $screenings = $candidate->screenings()
            ->whereIn('screening_type', $requiredTypes)
            ->get()
            ->keyBy('screening_type');

        $progress = [];
        foreach ($requiredTypes as $type) {
            $screening = $screenings->get($type);
            $progress[$type] = [
                'label' => self::getScreeningTypes()[$type] ?? $type,
                'status' => $screening ? $screening->status : 'not_started',
                'status_label' => $screening ? $screening->status_label : 'Not Started',
                'status_color' => $screening ? $screening->status_color : 'secondary',
                'screened_at' => $screening && $screening->screened_at
                    ? $screening->screened_at->format('Y-m-d H:i')
                    : null,
                'screener' => $screening && $screening->screener
                    ? $screening->screener->name
                    : null,
                'call_attempts' => $type === self::TYPE_CALL && $screening
                    ? $screening->call_attempt_display
                    : null,
            ];
        }

        // Calculate overall progress
        $passedCount = collect($progress)->where('status', self::STATUS_PASSED)->count();
        $failedCount = collect($progress)->where('status', self::STATUS_FAILED)->count();

        return [
            'screenings' => $progress,
            'passed_count' => $passedCount,
            'failed_count' => $failedCount,
            'total_required' => count($requiredTypes),
            'is_complete' => $passedCount === count($requiredTypes),
            'has_failure' => $failedCount > 0,
            'progress_percentage' => round(($passedCount / count($requiredTypes)) * 100),
        ];
    }

    /**
     * Check and update candidate status based on all screenings.
     */
    protected function checkAndUpdateCandidateStatus()
    {
        $candidate = $this->candidate;
        
        // Get required screening types
        $requiredTypes = [self::TYPE_DESK, self::TYPE_CALL, self::TYPE_PHYSICAL];
        
        // Check if all required screenings are passed
        $passedScreenings = $candidate->screenings()
                                      ->whereIn('screening_type', $requiredTypes)
                                      ->where('status', self::STATUS_PASSED)
                                      ->pluck('screening_type')
                                      ->toArray();
        
        $allPassed = count(array_diff($requiredTypes, $passedScreenings)) === 0;
        
        if ($allPassed && $candidate->status === 'screening') {
            $candidate->updateStatus('registered', 'All screenings passed');
        }
    }

    /**
     * Get summary statistics for this screening.
     */
    public function getSummaryStats()
    {
        return [
            'type' => $this->screening_type_label,
            'status' => $this->status_label,
            'attempts' => $this->screening_type === self::TYPE_CALL ? $this->call_count : null,
            'duration' => $this->formatted_call_duration,
            'screener' => $this->screener ? $this->screener->name : null,
            'date' => $this->screened_at ? $this->screened_at->format('Y-m-d H:i') : null,
        ];
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Track who created the record
        static::creating(function ($screening) {
            if (auth()->check()) {
                $screening->created_by = auth()->id();
            }
        });

        // Track who updated the record
        static::updating(function ($screening) {
            if (auth()->check()) {
                $screening->updated_by = auth()->id();
            }
        });
    }
}