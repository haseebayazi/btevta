<?php
// ============================================
// File: app/Models/DocumentArchive.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentArchive extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'campus_id',
        'oep_id',
        'document_category',
        'document_type',
        'document_name',
        'document_number',        // ADDED - Used in controller validation
        'file_path',
        'file_type',
        'file_size',
        'version',
        'uploaded_by',
        'uploaded_at',
        'is_current_version',
        'replaces_document_id',
        'issue_date',             // ADDED - Used in controller validation
        'expiry_date',            // ADDED - Used in controller validation
        'description',            // ADDED - Used in controller validation
        'tags',                   // ADDED - Used in controller validation
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'is_current_version' => 'boolean',
        'issue_date' => 'date',         // ADDED - Cast for date field
        'expiry_date' => 'date',        // ADDED - Cast for date field
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * SECURITY: Hide file paths to prevent unauthorized access
     */
    protected $hidden = [
        'file_path',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function oep()
    {
        return $this->belongsTo(Oep::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
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
     * Get access logs from Spatie activity log
     * ADDED - Used by controller to show access history
     */
    public function accessLogs()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject')
                    ->orderBy('created_at', 'desc');
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
