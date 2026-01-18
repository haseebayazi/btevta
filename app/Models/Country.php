<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'code_2',
        'currency_code',
        'phone_code',
        'is_destination',
        'specific_requirements',
        'is_active',
    ];

    protected $casts = [
        'is_destination' => 'boolean',
        'is_active' => 'boolean',
        'specific_requirements' => 'array',
    ];

    /**
     * Get programs for this country
     */
    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    /**
     * Get implementing partners in this country
     */
    public function implementingPartners()
    {
        return $this->hasMany(ImplementingPartner::class);
    }

    /**
     * Get employers in this country
     */
    public function employers()
    {
        return $this->hasMany(Employer::class);
    }

    /**
     * Scope to get only destination countries
     */
    public function scopeDestinations($query)
    {
        return $query->where('is_destination', true);
    }

    /**
     * Scope to get only active countries
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
