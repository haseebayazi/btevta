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
        'candidate_id', 'interview_date', 'interview_status', 'interview_completed', 'interview_remarks',
        'trade_test_date', 'trade_test_status', 'trade_test_completed', 'trade_test_remarks',
        'takamol_date', 'takamol_status', 'takamol_remarks', 'medical_date', 'medical_status', 'medical_completed',
        'medical_remarks', 'biometric_date', 'biometric_status', 'biometric_completed', 'biometric_remarks',
        'visa_date', 'visa_number', 'visa_status', 'visa_issued', 'visa_remarks', 'ticket_uploaded',
        'ticket_date', 'ticket_path', 'ticket_number', 'overall_status', 'remarks', 'created_by', 'updated_by'
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
        'ticket_number',
        'ticket_path',
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