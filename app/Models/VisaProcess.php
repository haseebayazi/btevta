<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisaProcess extends Model
{
    use SoftDeletes;

    protected $table = 'visa_processes';

    protected $fillable = [
        'candidate_id', 'interview_date', 'interview_status', 'interview_remarks',
        'trade_test_date', 'trade_test_status', 'trade_test_remarks',
        'takamol_date', 'takamol_status', 'medical_date', 'medical_status',
        'biometric_date', 'biometric_status', 'visa_date', 'visa_number',
        'visa_status', 'ticket_uploaded', 'ticket_date', 'ticket_path',
        'overall_status', 'remarks'
    ];

    protected $casts = [
        'interview_date' => 'date',
        'trade_test_date' => 'date',
        'takamol_date' => 'date',
        'medical_date' => 'date',
        'biometric_date' => 'date',
        'visa_date' => 'date',
        'ticket_date' => 'date',
        'ticket_uploaded' => 'boolean',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}