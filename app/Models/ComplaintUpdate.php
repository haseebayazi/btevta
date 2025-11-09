<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComplaintUpdate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'complaint_id',
        'user_id',
        'message',
        'status_changed_from',
        'status_changed_to',
        'priority_changed_from',
        'priority_changed_to',
        'assigned_from',
        'assigned_to',
        'is_internal',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'is_internal' => false,
    ];

    // Relationships
    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors
    public function getIsStatusChangeAttribute()
    {
        return !is_null($this->status_changed_from) && !is_null($this->status_changed_to);
    }

    public function getIsPriorityChangeAttribute()
    {
        return !is_null($this->priority_changed_from) && !is_null($this->priority_changed_to);
    }

    public function getIsAssignmentChangeAttribute()
    {
        return !is_null($this->assigned_from) || !is_null($this->assigned_to);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($update) {
            if (auth()->check()) {
                $update->user_id = $update->user_id ?? auth()->id();
                $update->created_by = auth()->id();
            }
        });

        static::updating(function ($update) {
            if (auth()->check()) {
                $update->updated_by = auth()->id();
            }
        });
    }
}
