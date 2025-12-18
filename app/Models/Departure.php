<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Departure extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'departure_date',
        'flight_number',
        'destination',
        'pre_departure_briefing',
        'briefing_date',
        'briefing_completed',
        'ready_for_departure',
        'iqama_number',
        'iqama_issue_date',
        'iqama_expiry_date',
        'post_arrival_medical_path',
        'absher_registered',
        'absher_registration_date',
        'absher_id',
        'absher_verification_status',
        'qiwa_id',
        'qiwa_activated',
        'qiwa_activation_date',
        'qiwa_status',
        'salary_amount',
        'salary_currency',
        'first_salary_date',
        'salary_confirmed',
        'salary_confirmation_date',
        'salary_remarks',
        'ninety_day_report_submitted',
        'remarks',
        'created_by',
        'updated_by',
        // Additional fields for service compatibility
        'pre_briefing_date',
        'pre_briefing_conducted_by',
        'briefing_topics',
        'briefing_remarks',
        'current_stage',
        'airport',
        'country_code',
        'departure_remarks',
        'medical_report_path',
        'medical_report_date',
        'accommodation_type',
        'accommodation_address',
        'accommodation_status',
        'accommodation_verified_date',
        'accommodation_remarks',
        'employer_name',
        'employer_contact',
        'employer_address',
        'employer_id_number',
        'communication_logs',
        'last_contact_date',
        'compliance_verified_date',
        'compliance_remarks',
        'issues',
        'return_date',
        'return_reason',
        'return_remarks',
        'salary_proof_path',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'briefing_date' => 'date',
        'pre_briefing_date' => 'date',
        'iqama_issue_date' => 'date',
        'iqama_expiry_date' => 'date',
        'absher_registration_date' => 'date',
        'qiwa_activation_date' => 'date',
        'first_salary_date' => 'date',
        'salary_confirmation_date' => 'date',
        'accommodation_verified_date' => 'date',
        'last_contact_date' => 'date',
        'medical_report_date' => 'date',
        'compliance_verified_date' => 'date',
        'return_date' => 'date',
        'salary_amount' => 'float',
        'pre_departure_briefing' => 'boolean',
        'briefing_completed' => 'boolean',
        'ready_for_departure' => 'boolean',
        'absher_registered' => 'boolean',
        'qiwa_activated' => 'boolean',
        'salary_confirmed' => 'boolean',
        'ninety_day_report_submitted' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * SECURITY: Hide sensitive employment and financial information
     */
    protected $hidden = [
        'iqama_number',
        'qiwa_id',
        'salary_amount',
        'post_arrival_medical_path',
    ];

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
            $q->where('flight_number', 'like', "%{$escapedTerm}%")
              ->orWhere('destination', 'like', "%{$escapedTerm}%")
              ->orWhereHas('candidate', function($subQ) use ($escapedTerm) {
                  $subQ->where('name', 'like', "%{$escapedTerm}%")
                       ->orWhere('cnic', 'like', "%{$escapedTerm}%")
                       ->orWhere('btevta_id', 'like', "%{$escapedTerm}%");
              });
        });
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