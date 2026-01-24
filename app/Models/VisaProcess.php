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
        'visa_partner_id',
        // Interview & Trade Test
        'interview_date', 'interview_status', 'interview_completed', 'interview_remarks',
        'trade_test_date', 'trade_test_status', 'trade_test_completed', 'trade_test_remarks',
        // Takamol Test
        'takamol_date', 'takamol_status',
        // Medical/GAMCA
        'medical_date', 'medical_status', 'medical_completed',
        // E-Number
        'enumber',
        // Biometrics/Etimad
        'biometric_date', 'etimad_appointment_id', 'biometric_status', 'biometric_completed',
        // Visa & PTN
        'visa_date', 'visa_number', 'visa_status', 'visa_issued',
        'ptn_number',
        // Ticket & Travel
        'ticket_uploaded', 'ticket_date', 'ticket_path', 'travel_plan_path',
        // General
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
    /**
     * Set takamol booking date (ignored - not in database schema).
     * For backward compatibility with tests.
     */
    public function setTakamolBookingDateAttribute($value)
    {
        // Ignore - this field doesn't exist in the database schema
    }

    /**
     * Set GAMCA booking date (ignored - not in database schema).
     * For backward compatibility with tests.
     */
    public function setGamcaBookingDateAttribute($value)
    {
        // Ignore - this field doesn't exist in the database schema
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function visaPartner()
    {
        return $this->belongsTo(VisaPartner::class);
    }

    /**
     * Get the OEP through the candidate relationship.
     * PHASE 2 FIX: Use HasOneThrough with correct key ordering.
     *
     * Note: For direct access, use $visaProcess->candidate->oep instead.
     *
     * @see docs/IMPLEMENTATION_PLAN.md - Phase 2.3
     */
    public function oep()
    {
        // HasOneThrough requires correct key ordering:
        // 1st arg: Final model we want (Oep)
        // 2nd arg: Intermediate model (Candidate)
        // 3rd arg: FK on intermediate table (candidates.id matches visa_processes.candidate_id)
        // 4th arg: FK on final table (oeps.id)
        // 5th arg: Local key on this model (visa_processes.candidate_id)
        // 6th arg: Local key on intermediate model (candidates.oep_id)
        return $this->hasOneThrough(
            Oep::class,
            Candidate::class,
            'id',           // candidates.id
            'id',           // oeps.id
            'candidate_id', // visa_processes.candidate_id
            'oep_id'        // candidates.oep_id
        );
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