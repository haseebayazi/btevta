<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImplementingPartner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'contact_email',
        'contact_phone',
        'address',
        'city',
        'country_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the country for this implementing partner
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get candidates assigned to this implementing partner
     */
    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    /**
     * Scope to get only active implementing partners
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
