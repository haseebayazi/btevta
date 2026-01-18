<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'duration_weeks',
        'country_id',
        'is_active',
    ];

    protected $casts = [
        'duration_weeks' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the country associated with this program
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get courses for this program
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Get candidates enrolled in this program
     */
    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    /**
     * Scope to get only active programs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
