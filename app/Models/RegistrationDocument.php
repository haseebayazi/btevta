<?php
// ============================================
// File: app/Models/RegistrationDocument.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RegistrationDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'document_type',
        'document_number',
        'file_path',
        'issue_date',
        'expiry_date',
        'status',
        'verification_status',
        'verification_remarks',
        'rejection_reason',
        'remarks',
        'uploaded_by',
        'verified_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * SECURITY: Hide file paths and document numbers to prevent unauthorized access
     */
    protected $hidden = [
        'file_path',
        'document_number',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Set is_verified (maps to verification_status).
     * For backward compatibility with tests.
     */
    public function setIsVerifiedAttribute($value)
    {
        // Map boolean to verification_status
        if ($value) {
            $this->attributes['verification_status'] = 'verified';
            $this->attributes['status'] = 'verified';
        } else {
            $this->attributes['verification_status'] = 'pending';
            $this->attributes['status'] = 'pending';
        }
    }

    /**
     * Get is_verified (reads from verification_status).
     * For backward compatibility with tests.
     */
    public function getIsVerifiedAttribute()
    {
        return isset($this->attributes['verification_status']) &&
               $this->attributes['verification_status'] === 'verified';
    }

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
    }
}
