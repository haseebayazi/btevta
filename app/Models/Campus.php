<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campus extends Model
{
    use SoftDeletes;

   protected $fillable = [
    'name', 'code', 'address', 'city', 'contact_person', 'phone', 'email', 'is_active'
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

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Add scope for convenience
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    public function departures()
    {
        return $this->hasManyThrough(Departure::class, Candidate::class);
    }

    public function visaProcesses()
    {
        return $this->hasManyThrough(VisaProcess::class, Candidate::class);
    }
}