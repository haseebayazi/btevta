<?php

namespace App\Models;

use App\Enums\ComplaintPriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'description_template',
        'required_evidence_types',
        'suggested_actions',
        'default_priority',
        'suggested_sla_hours',
        'is_active',
    ];

    protected $casts = [
        'required_evidence_types' => 'array',
        'suggested_actions'       => 'array',
        'default_priority'        => ComplaintPriority::class,
        'is_active'               => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
