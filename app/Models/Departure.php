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
        'post_arrival_medical_path',
        'absher_registered',
        'absher_registration_date',
        'qiwa_id',
        'qiwa_activated',
        'salary_amount',
        'first_salary_date',
        'ninety_day_report_submitted',
        'remarks',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'departure_date' => 'date',
        'briefing_date' => 'date',
        'iqama_issue_date' => 'date',
        'absher_registration_date' => 'date',
        'first_salary_date' => 'date',
        'salary_amount' => 'float',
        'pre_departure_briefing' => 'boolean',
        'briefing_completed' => 'boolean',
        'ready_for_departure' => 'boolean',
        'absher_registered' => 'boolean',
        'qiwa_activated' => 'boolean',
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
        return $query->where(function($q) use ($term) {
            $q->where('flight_number', 'like', "%{$term}%")
              ->orWhere('destination', 'like', "%{$term}%")
              ->orWhereHas('candidate', function($subQ) use ($term) {
                  $subQ->where('name', 'like', "%{$term}%")
                       ->orWhere('cnic', 'like', "%{$term}%")
                       ->orWhere('btevta_id', 'like', "%{$term}%");
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