<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VisaPartner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'company_name',
        'registration_number',
        'license_number',
        'contact_person',
        'phone',
        'email',
        'address',
        'website',
        'country',
        'city',
        'specialization',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function visaProcesses()
    {
        return $this->hasMany(VisaProcess::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    public function scopeSearch($query, $term)
    {
        $escapedTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return $query->where(function($q) use ($escapedTerm) {
            $q->where('name', 'like', "%{$escapedTerm}%")
              ->orWhere('code', 'like', "%{$escapedTerm}%")
              ->orWhere('company_name', 'like', "%{$escapedTerm}%")
              ->orWhere('contact_person', 'like', "%{$escapedTerm}%")
              ->orWhere('country', 'like', "%{$escapedTerm}%")
              ->orWhere('city', 'like', "%{$escapedTerm}%");
        });
    }

    // ============================================================
    // BOOT
    // ============================================================

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
