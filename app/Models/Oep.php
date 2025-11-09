<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Oep extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'company_name', 'registration_number', 'contact_person', 
        'phone', 'email', 'address', 'website', 'country', 'city', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    // FIX: This was missing and causing crash
    public function departures()
    {
        return $this->hasManyThrough(Departure::class, Candidate::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }
}