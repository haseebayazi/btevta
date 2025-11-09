<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Departure extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'candidate_id', 'departure_date', 'flight_number', 'destination',
        'pre_departure_briefing', 'briefing_date', 'iqama_number',
        'iqama_issue_date', 'post_arrival_medical_path', 'absher_registered',
        'absher_registration_date', 'qiwa_id', 'qiwa_activated',
        'salary_amount', 'first_salary_date', 'ninety_day_report_submitted', 'remarks'
    ];

    protected $casts = [
        'departure_date' => 'date',
        'briefing_date' => 'date',
        'iqama_issue_date' => 'date',
        'absher_registration_date' => 'date',
        'first_salary_date' => 'date',
        'salary_amount' => 'float',
        'pre_departure_briefing' => 'boolean',
        'absher_registered' => 'boolean',
        'qiwa_activated' => 'boolean',
        'ninety_day_report_submitted' => 'boolean',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}