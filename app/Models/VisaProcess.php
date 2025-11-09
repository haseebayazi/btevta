<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VisaProcess extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'visa_processes';

    protected $fillable = [
        'candidate_id', 'interview_date', 'interview_status', 'interview_remarks',
        'trade_test_date', 'trade_test_status', 'trade_test_remarks',
        'takamol_date', 'takamol_status', 'medical_date', 'medical_status',
        'biometric_date', 'biometric_status', 'visa_date', 'visa_number',
        'visa_status', 'ticket_uploaded', 'ticket_date', 'ticket_path', 'ticket_number',
        'overall_status', 'remarks', 'created_by', 'updated_by'
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

    /**
     * Boot method for automatic audit trail.
     */
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

    // Relationships
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
}