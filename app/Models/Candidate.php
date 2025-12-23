<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

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
        'trade_id',
        'oep_id',
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
        'photo_path',
        'remarks',
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
     * Status constants
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

    /**
     * Training status constants
     */
    const TRAINING_NOT_STARTED = 'not_started';
    const TRAINING_IN_PROGRESS = 'in_progress';
    const TRAINING_COMPLETED = 'completed';
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
        ];

        $currentStatus = $this->status;
        
        if (!isset($allowedTransitions[$currentStatus]) || 
            !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            throw new \Exception("Invalid status transition from {$currentStatus} to {$newStatus}");
        }

        $this->status = $newStatus;
        if ($remarks) {
            $this->remarks = $remarks;
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
     * Generate a unique BTEVTA ID for the candidate.
     */
    public static function generateBtevtaId()
    {
        $year = date('Y');
        $lastId = self::whereYear('created_at', $year)
                      ->where('btevta_id', 'like', 'BTV-' . $year . '-%')
                      ->max('btevta_id');

        if ($lastId) {
            $number = intval(substr($lastId, -5)) + 1;
        } else {
            $number = 1;
        }

        return 'BTV-' . $year . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
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