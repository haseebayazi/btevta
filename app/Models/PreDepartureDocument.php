<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreDepartureDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'document_checklist_id',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'notes',
        'uploaded_at',
        'uploaded_by',
        'verified_at',
        'verified_by',
        'verification_notes',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'uploaded_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the candidate that owns this document
     */
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Get the document checklist item
     */
    public function documentChecklist()
    {
        return $this->belongsTo(DocumentChecklist::class);
    }

    /**
     * Get the user who uploaded the document
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the user who verified the document
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Check if document is verified
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Scope to get only verified documents
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Scope to get only unverified documents
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('verified_at');
    }
}
