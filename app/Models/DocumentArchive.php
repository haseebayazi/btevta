<?php
// ============================================
// File: app/Models/DocumentArchive.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentArchive extends Model
{
    protected $fillable = [
        'candidate_id', 'campus_id', 'oep_id', 'document_category',
        'document_type', 'document_name', 'file_path', 'file_type',
        'file_size', 'version', 'uploaded_by', 'uploaded_at',
        'is_current_version', 'replaces_document_id'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'is_current_version' => 'boolean',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
