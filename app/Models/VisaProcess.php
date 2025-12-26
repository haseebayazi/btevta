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
        'candidate_id',
        // Interview & Trade Test
        'interview_date', 'interview_status', 'interview_completed', 'interview_remarks',
        'trade_test_date', 'trade_test_status', 'trade_test_completed', 'trade_test_remarks',
        // Takamol Test
        'takamol_date', 'takamol_booking_date', 'takamol_status', 'takamol_remarks',
        'takamol_result_path', 'takamol_score',
        // Medical/GAMCA
        'medical_date', 'gamca_booking_date', 'medical_status', 'medical_completed',
        'medical_remarks', 'gamca_result_path', 'gamca_barcode', 'gamca_expiry_date',
        // E-Number
        'enumber', 'enumber_date', 'enumber_status',
        // Biometrics/Etimad
        'biometric_date', 'etimad_appointment_id', 'etimad_center', 'biometric_status',
        'biometric_completed', 'biometric_remarks',
        // Visa Documents Submission
        'visa_submission_date', 'visa_application_number', 'embassy',
        // Visa & PTN
        'visa_date', 'visa_number', 'visa_status', 'visa_issued', 'visa_remarks',
        'ptn_number', 'ptn_issue_date', 'attestation_date',
        // Ticket & Travel
        'ticket_uploaded', 'ticket_date', 'ticket_path', 'ticket_number',
        'flight_number', 'departure_date', 'arrival_date', 'travel_plan_path',
        // General
        'overall_status', 'current_stage', 'remarks', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'interview_date' => 'date',
        'trade_test_date' => 'date',
        'takamol_date' => 'date',
        'takamol_booking_date' => 'date',
        'medical_date' => 'date',
        'gamca_booking_date' => 'date',
        'gamca_expiry_date' => 'date',
        'enumber_date' => 'date',
        'biometric_date' => 'date',
        'visa_submission_date' => 'date',
        'visa_date' => 'date',
        'ptn_issue_date' => 'date',
        'attestation_date' => 'date',
        'ticket_date' => 'date',
        'departure_date' => 'datetime',
        'arrival_date' => 'datetime',
        'interview_completed' => 'boolean',
        'trade_test_completed' => 'boolean',
        'medical_completed' => 'boolean',
        'biometric_completed' => 'boolean',
        'visa_issued' => 'boolean',
        'ticket_uploaded' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * SECURITY: Hide sensitive visa and document information
     */
    protected $hidden = [
        'visa_number',
        'ticket_number',
        'ticket_path',
        'ptn_number',
    ];

    /**
     * Visa processing stages configuration
     */
    const STAGES = [
        'initiated' => ['label' => 'Initiated', 'order' => 1, 'color' => 'secondary'],
        'interview' => ['label' => 'Interview', 'order' => 2, 'color' => 'info'],
        'trade_test' => ['label' => 'Trade Test', 'order' => 3, 'color' => 'info'],
        'takamol' => ['label' => 'Takamol Test', 'order' => 4, 'color' => 'info'],
        'medical' => ['label' => 'Medical (GAMCA)', 'order' => 5, 'color' => 'info'],
        'enumber' => ['label' => 'E-Number', 'order' => 6, 'color' => 'info'],
        'biometrics' => ['label' => 'Biometrics (Etimad)', 'order' => 7, 'color' => 'info'],
        'visa_submission' => ['label' => 'Visa Submission', 'order' => 8, 'color' => 'warning'],
        'visa_issued' => ['label' => 'Visa & PTN', 'order' => 9, 'color' => 'primary'],
        'ticket' => ['label' => 'Ticket & Travel', 'order' => 10, 'color' => 'success'],
        'completed' => ['label' => 'Completed', 'order' => 11, 'color' => 'success'],
    ];

    /**
     * Get all stages
     */
    public static function getStages()
    {
        return self::STAGES;
    }

    /**
     * Get current stage info
     */
    public function getCurrentStageInfo()
    {
        return self::STAGES[$this->overall_status] ?? self::STAGES['initiated'];
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute()
    {
        $currentOrder = $this->getCurrentStageInfo()['order'];
        $totalStages = count(self::STAGES) - 1; // Exclude completed
        return min(100, round(($currentOrder / $totalStages) * 100));
    }

    /**
     * Check if stage is completed
     */
    public function isStageCompleted($stage)
    {
        $currentOrder = $this->getCurrentStageInfo()['order'];
        $stageOrder = self::STAGES[$stage]['order'] ?? 0;
        return $currentOrder > $stageOrder;
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return $this->getCurrentStageInfo()['color'] ?? 'secondary';
    }

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

    public function oep()
    {
        return $this->hasOneThrough(Oep::class, Candidate::class, 'id', 'id', 'candidate_id', 'oep_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeSearch($query, $term)
    {
        // Escape special LIKE characters to prevent SQL LIKE injection
        $escapedTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return $query->where(function($q) use ($escapedTerm) {
            $q->where('overall_status', 'like', "%{$escapedTerm}%")
              ->orWhereHas('candidate', function($subQ) use ($escapedTerm) {
                  $subQ->where('name', 'like', "%{$escapedTerm}%")
                       ->orWhere('cnic', 'like', "%{$escapedTerm}%")
                       ->orWhere('btevta_id', 'like', "%{$escapedTerm}%");
              });
        });
    }
}