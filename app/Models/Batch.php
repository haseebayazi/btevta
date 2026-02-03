<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Batch extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'batches';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'batch_code',
        'name',
        'campus_id',
        'program_id', // FIXED: Missing field needed by AutoBatchService
        'trade_id',
        'oep_id',  // FIXED: Missing field causing silent data loss
        'capacity',
        'start_date',
        'end_date',
        'intake_period',
        'district',
        'specialization',
        'status',
        'trainer_id',
        'coordinator_id',
        'description',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'capacity' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'enrollment_count',
        'available_slots',
        'is_full',
        'is_active',
        'status_badge_class',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'status' => 'planned',
        'capacity' => 30,
    ];

    /**
     * Status constants
     */
    const STATUS_PLANNED = 'planned';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get all status types
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_PLANNED => 'Planned',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the campus this batch belongs to.
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the trade associated with this batch.
     */
    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    /**
     * Get the OEP associated with this batch.
     */
    public function oep()
    {
        return $this->belongsTo(Oep::class);
    }

    /**
     * Get all candidates in this batch.
     */
    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    /**
     * Get the trainer assigned to this batch.
     */
    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Get the coordinator assigned to this batch.
     */
    public function coordinator()
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    /**
     * Get all training schedules for this batch.
     */
    public function trainingSchedules()
    {
        return $this->hasMany(TrainingSchedule::class);
    }

    /**
     * Get all assessments conducted for this batch.
     */
    public function assessments()
    {
        return $this->hasManyThrough(TrainingAssessment::class, Candidate::class);
    }

    /**
     * Get the user who created this batch.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this batch.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to get active batches.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get upcoming batches.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', self::STATUS_PLANNED)
                     ->where('start_date', '>', now());
    }

    /**
     * Scope to filter by campus.
     */
    public function scopeByCampus($query, $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Scope to filter by district.
     */
    public function scopeByDistrict($query, $district)
    {
        return $query->where('district', $district);
    }

    /**
     * Scope to search batches.
     */
    public function scopeSearch($query, $search)
    {
        // Escape special LIKE characters to prevent SQL LIKE injection
        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);

        return $query->where(function ($q) use ($escapedSearch) {
            $q->where('batch_code', 'like', "%{$escapedSearch}%")
              ->orWhere('name', 'like', "%{$escapedSearch}%")
              ->orWhere('specialization', 'like', "%{$escapedSearch}%");
        });
    }

    /**
     * Scope to get planned batches.
     */
    public function scopePlanned($query)
    {
        return $query->where('status', self::STATUS_PLANNED);
    }

    /**
     * Scope to get completed batches.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to filter by trade.
     */
    public function scopeByTrade($query, $tradeId)
    {
        return $query->where('trade_id', $tradeId);
    }

    /**
     * Scope to filter by OEP.
     */
    public function scopeByOep($query, $oepId)
    {
        return $query->where('oep_id', $oepId);
    }

    /**
     * Scope to get batches with available slots.
     */
    public function scopeAvailable($query)
    {
        return $query->whereRaw('(SELECT COUNT(*) FROM candidates WHERE candidates.batch_id = batches.id AND candidates.deleted_at IS NULL) < batches.capacity');
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Get the current enrollment count.
     */
    public function getEnrollmentCountAttribute()
    {
        return $this->candidates()->count();
    }

    /**
     * Get available slots in the batch.
     */
    public function getAvailableSlotsAttribute()
    {
        return max(0, $this->capacity - $this->enrollment_count);
    }

    /**
     * Check if batch is full.
     */
    public function getIsFullAttribute()
    {
        return $this->enrollment_count >= $this->capacity;
    }

    /**
     * Get current size (alias for enrollment_count).
     * For backward compatibility with tests.
     */
    public function getCurrentSizeAttribute()
    {
        return $this->enrollment_count;
    }

    /**
     * Set current size (ignored - computed field).
     * For backward compatibility with tests that try to set this.
     */
    public function setCurrentSizeAttribute($value)
    {
        // Ignore - this is a computed field based on candidates count
        // Tests may try to set this, but it should not be persisted
    }

    /**
     * Get max size (alias for capacity).
     * For backward compatibility with tests.
     */
    public function getMaxSizeAttribute()
    {
        return $this->capacity;
    }

    /**
     * Set max size (maps to capacity).
     * For backward compatibility with tests.
     */
    public function setMaxSizeAttribute($value)
    {
        $this->attributes['capacity'] = $value;
    }

    /**
     * Get batch duration in days.
     */
    public function getDurationInDaysAttribute()
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date);
        }
        return null;
    }

    /**
     * Get batch progress percentage.
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->status !== self::STATUS_ACTIVE || !$this->start_date || !$this->end_date) {
            return 0;
        }

        $totalDays = $this->start_date->diffInDays($this->end_date);
        $elapsedDays = $this->start_date->diffInDays(now());

        if ($totalDays > 0) {
            return min(100, round(($elapsedDays / $totalDays) * 100, 2));
        }

        return 0;
    }

    /**
     * Check if batch is currently active.
     */
    public function getIsActiveAttribute()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get status badge CSS class for UI display.
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            self::STATUS_PLANNED => 'bg-blue-100 text-blue-800',
            self::STATUS_ACTIVE => 'bg-green-100 text-green-800',
            self::STATUS_COMPLETED => 'bg-gray-100 text-gray-800',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    // ==================== HELPER METHODS ====================

    /**
     * Generate a unique batch code.
     */
    public static function generateBatchCode($campusCode = null, $tradeCode = null)
    {
        $year = date('Y');
        $month = date('m');
        $campus = $campusCode ?? 'XX';
        $trade = $tradeCode ?? 'XX';
        
        $lastBatch = self::where('batch_code', 'like', "BATCH-{$campus}-{$trade}-{$year}{$month}%")
                         ->orderBy('batch_code', 'desc')
                         ->first();
        
        if ($lastBatch) {
            $lastNumber = intval(substr($lastBatch->batch_code, -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf("BATCH-%s-%s-%s%s-%03d", $campus, $trade, $year, $month, $newNumber);
    }

    /**
     * Check if batch is full.
     */
    public function isFull()
    {
        return $this->is_full;
    }

    /**
     * Check if batch can accommodate additional candidates.
     */
    public function canAddCandidates($count = 1)
    {
        return ($this->enrollment_count + $count) <= $this->capacity;
    }

    /**
     * Get enrollment progress percentage.
     */
    public function getEnrollmentProgressPercentage()
    {
        if ($this->capacity == 0) return 0;
        return round(($this->enrollment_count / $this->capacity) * 100, 2);
    }

    /**
     * Add a candidate to the batch.
     */
    public function addCandidate($candidateId)
    {
        if ($this->isFull()) {
            throw new \Exception("Batch is at full capacity ({$this->capacity})");
        }
        
        $candidate = Candidate::findOrFail($candidateId);
        $candidate->batch_id = $this->id;
        $candidate->save();
        
        return true;
    }

    /**
     * Start the batch.
     */
    public function start()
    {
        if ($this->status !== self::STATUS_PLANNED) {
            throw new \Exception("Only planned batches can be started");
        }
        
        $this->status = self::STATUS_ACTIVE;
        $this->start_date = $this->start_date ?? now();
        $this->save();
        
        // Update all candidates' training status
        $this->candidates()->update([
            'training_status' => 'in_progress',
            'training_start_date' => $this->start_date
        ]);
        
        return true;
    }

    /**
     * Complete the batch.
     */
    public function complete()
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            throw new \Exception("Only active batches can be completed");
        }
        
        $this->status = self::STATUS_COMPLETED;
        $this->end_date = $this->end_date ?? now();
        $this->save();
        
        // Update candidates who completed training
        $this->candidates()
              ->where('training_status', 'in_progress')
              ->update([
                  'training_status' => 'completed',
                  'training_end_date' => $this->end_date
              ]);
        
        return true;
    }

    /**
     * Get batch statistics.
     */
    public function getStatistics()
    {
        $candidates = $this->candidates;
        
        return [
            'total_enrolled' => $candidates->count(),
            'capacity' => $this->capacity,
            'available_slots' => $this->available_slots,
            'active_candidates' => $candidates->where('training_status', 'in_progress')->count(),
            'completed_candidates' => $candidates->where('training_status', 'completed')->count(),
            'dropped_candidates' => $candidates->where('training_status', 'dropped')->count(),
            'average_attendance' => $this->calculateAverageAttendance(),
            'progress_percentage' => $this->progress_percentage,
        ];
    }

    /**
     * Calculate average attendance for the batch.
     */
    protected function calculateAverageAttendance()
    {
        $totalAttendance = 0;
        $candidateCount = 0;
        
        foreach ($this->candidates as $candidate) {
            $attendance = $candidate->getAttendancePercentage($this->start_date, $this->end_date);
            if ($attendance > 0) {
                $totalAttendance += $attendance;
                $candidateCount++;
            }
        }
        
        return $candidateCount > 0 ? round($totalAttendance / $candidateCount, 2) : 0;
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate UUID and batch code on creation
        static::creating(function ($batch) {
            if (empty($batch->uuid)) {
                $batch->uuid = Str::uuid();
            }
            
            if (empty($batch->batch_code)) {
                $campusCode = $batch->campus ? $batch->campus->code : null;
                $tradeCode = $batch->trade ? $batch->trade->code : null;
                $batch->batch_code = self::generateBatchCode($campusCode, $tradeCode);
            }
            
            if (auth()->check()) {
                $batch->created_by = auth()->id();
            }
        });

        // Track who updated the record
        static::updating(function ($batch) {
            if (auth()->check()) {
                $batch->updated_by = auth()->id();
            }
        });
    }
}