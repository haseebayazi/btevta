<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_id',
        'batch_id',
        'user_id',
        'usage_type',
        'start_time',
        'end_time',
        'hours_used',
        'students_count',
        'notes',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'hours_used' => 'decimal:2',
    ];

    public const USAGE_TYPES = [
        'training' => 'Training Session',
        'maintenance' => 'Maintenance',
        'idle' => 'Idle',
        'repair' => 'Repair',
    ];

    // Relationships
    public function equipment()
    {
        return $this->belongsTo(CampusEquipment::class, 'equipment_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Calculate hours when end_time is set
    protected static function booted()
    {
        static::saving(function ($log) {
            if ($log->start_time && $log->end_time) {
                $log->hours_used = $log->start_time->diffInMinutes($log->end_time) / 60;
                $log->status = 'completed';
            }
        });
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForTraining($query)
    {
        return $query->where('usage_type', 'training');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('start_time', now()->month)
            ->whereYear('start_time', now()->year);
    }
}
