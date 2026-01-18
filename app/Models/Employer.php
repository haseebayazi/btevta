<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'permission_number',
        'visa_issuing_company',
        'country_id',
        'sector',
        'trade',
        'basic_salary',
        'salary_currency',
        'food_by_company',
        'transport_by_company',
        'accommodation_by_company',
        'other_conditions',
        'evidence_path',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'food_by_company' => 'boolean',
        'transport_by_company' => 'boolean',
        'accommodation_by_company' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the country for this employer
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the user who created this employer
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get candidates associated with this employer
     */
    public function candidates()
    {
        return $this->belongsToMany(Candidate::class, 'candidate_employer')
            ->withPivot('is_current', 'assigned_at', 'assigned_by')
            ->withTimestamps();
    }

    /**
     * Get current candidates for this employer
     */
    public function currentCandidates()
    {
        return $this->candidates()->wherePivot('is_current', true);
    }

    /**
     * Scope to get only active employers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
