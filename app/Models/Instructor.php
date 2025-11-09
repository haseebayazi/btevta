<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Instructor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'cnic',
        'email',
        'phone',
        'address',
        'qualification',
        'specialization',
        'experience_years',
        'campus_id',
        'trade_id',
        'employment_type',
        'joining_date',
        'status',
        'photo_path',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'joining_date' => 'date',
        'experience_years' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'active',
        'employment_type' => 'permanent',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ON_LEAVE = 'on_leave';
    const STATUS_TERMINATED = 'terminated';

    // Employment type constants
    const EMPLOYMENT_PERMANENT = 'permanent';
    const EMPLOYMENT_CONTRACT = 'contract';
    const EMPLOYMENT_VISITING = 'visiting';

    public static function getStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_ON_LEAVE => 'On Leave',
            self::STATUS_TERMINATED => 'Terminated',
        ];
    }

    public static function getEmploymentTypes()
    {
        return [
            self::EMPLOYMENT_PERMANENT => 'Permanent',
            self::EMPLOYMENT_CONTRACT => 'Contract',
            self::EMPLOYMENT_VISITING => 'Visiting',
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

    public function trainingClasses()
    {
        return $this->hasMany(TrainingClass::class);
    }

    public function attendances()
    {
        return $this->hasMany(TrainingAttendance::class, 'trainer_id');
    }

    public function assessments()
    {
        return $this->hasMany(TrainingAssessment::class, 'trainer_id');
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
        return $query->where('status', self::STATUS_ACTIVE);
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
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'secondary',
            self::STATUS_ON_LEAVE => 'warning',
            self::STATUS_TERMINATED => 'danger',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($instructor) {
            if (auth()->check()) {
                $instructor->created_by = auth()->id();
            }
        });

        static::updating(function ($instructor) {
            if (auth()->check()) {
                $instructor->updated_by = auth()->id();
            }
        });
    }
}
