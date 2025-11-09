<?php
// ============================================
// File: app/Models/RegistrationDocument.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationDocument extends Model
{
    protected $fillable = [
        'candidate_id', 'document_type', 'document_number',
        'file_path', 'issue_date', 'expiry_date',
        'verification_status', 'remarks'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
