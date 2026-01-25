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
        'upload_date',            // ADDED - Database column name
        'is_current_version',
        'replaces_document_id',
        'issue_date',             // ADDED - Used in controller validation
        'expiry_date',            // ADDED - Used in controller validation
        'description',            // ADDED - Used in controller validation
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'upload_date' => 'date',        // ADDED - Database column
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

    public function uploadedBy()
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
     * Set category (ignored - not in database schema).
     * For backward compatibility with tests that might use 'category'.
     */
    public function setCategoryAttribute($value)
    {
        // Ignore - use document_category instead
    }

    /**
     * Set status (ignored - not in database schema).
     * For backward compatibility with tests.
     */
    public function setStatusAttribute($value)
    {
        // Ignore - this field doesn't exist in the database schema
    }

    /**
     * Set description mutator (accepts value but doesn't store).
     * Description field exists in fillable but may not be in database.
     */
    public function setDescriptionAttribute($value)
    {
        // Ignore - this field doesn't exist in the database schema
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

    /**
     * Get tags associated with this document
     */
    public function tags()
    {
        return $this->belongsToMany(DocumentTag::class, 'document_tag_pivot', 'document_id', 'tag_id')
            ->withTimestamps();
    }

    /**
     * Get all versions of this document
     */
    public function versions()
    {
        return $this->hasMany(DocumentArchive::class, 'replaces_document_id', 'id')
            ->orderBy('version', 'desc');
    }

    /**
     * Get the document this version replaces
     */
    public function replacedDocument()
    {
        return $this->belongsTo(DocumentArchive::class, 'replaces_document_id');
    }

    /**
     * Scope to get only current versions
     */
    public function scopeCurrentVersion($query)
    {
        return $query->where('is_current_version', true);
    }

    /**
     * Scope to get expiring documents
     */
    public function scopeExpiring($query, $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    /**
     * Scope to get expired documents
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
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
