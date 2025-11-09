<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'training_classes';

    protected $fillable = [
        'class_name',
        'class_code',
        'campus_id',
        'trade_id',
        'instructor_id',
        'batch_id',
        'start_date',
        'end_date',
        'max_capacity',
        'current_enrollment',
        'schedule',
        'room_number',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'max_capacity' => 'integer',
        'current_enrollment' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'scheduled',
        'current_enrollment' => 0,
    ];

    // Status constants
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_ONGOING = 'ongoing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public static function getStatuses()
    {
        return [
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_ONGOING => 'Ongoing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    // Relationships
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function candidates()
    {
        return $this->belongsToMany(Candidate::class, 'class_enrollments')
                    ->withPivot('enrolled_at', 'status', 'completion_date', 'remarks')
                    ->withTimestamps();
    }

    public function attendances()
    {
        return $this->hasMany(TrainingAttendance::class, 'class_id');
    }

    public function assessments()
    {
        return $this->hasMany(TrainingAssessment::class, 'class_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_ONGOING]);
    }

    public function scopeByCampus($query, $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    public function scopeByTrade($query, $tradeId)
    {
        return $query->where('trade_id', $tradeId);
    }

    // Accessors
    public function getStatusBadgeColorAttribute()
    {
        $colors = [
            self::STATUS_SCHEDULED => 'info',
            self::STATUS_ONGOING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getAvailableSlotsAttribute()
    {
        return $this->max_capacity - $this->current_enrollment;
    }

    public function getIsFullAttribute()
    {
        return $this->current_enrollment >= $this->max_capacity;
    }

    public function getCapacityPercentageAttribute()
    {
        if ($this->max_capacity == 0) {
            return 0;
        }

        return round(($this->current_enrollment / $this->max_capacity) * 100, 2);
    }

    // Helper Methods
    public function enrollCandidate($candidateId, $remarks = null)
    {
        if ($this->is_full) {
            throw new \Exception('Class is full');
        }

        $this->candidates()->attach($candidateId, [
            'enrolled_at' => now(),
            'status' => 'enrolled',
            'remarks' => $remarks
        ]);

        $this->increment('current_enrollment');

        return true;
    }

    public function removeCandidate($candidateId)
    {
        $this->candidates()->detach($candidateId);
        $this->decrement('current_enrollment');

        return true;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($class) {
            if (auth()->check()) {
                $class->created_by = auth()->id();
            }

            // Generate class code if not provided
            if (empty($class->class_code)) {
                $class->class_code = 'CLS-' . strtoupper(uniqid());
            }
        });

        static::updating(function ($class) {
            if (auth()->check()) {
                $class->updated_by = auth()->id();
            }
        });
    }
}
