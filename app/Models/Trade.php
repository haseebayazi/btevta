<?php
// ============================================
// File: app/Models/Trade.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    protected $fillable = [
        'name', 'code', 'description', 'duration_months', 'is_active'
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}