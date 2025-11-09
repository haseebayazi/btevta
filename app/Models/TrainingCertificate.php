<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingCertificate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'candidate_id', 'batch_id', 'certificate_number', 'issue_date',
        'validity_period', 'certificate_path', 'status', 'remarks'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'validity_period' => 'date',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}