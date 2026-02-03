<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'category',
        'is_mandatory',
        'supports_multiple_pages',
        'max_pages',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'supports_multiple_pages' => 'boolean',
        'max_pages' => 'integer',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get pre-departure documents of this type
     */
    public function preDepartureDocuments()
    {
        return $this->hasMany(PreDepartureDocument::class);
    }

    /**
     * Scope to get only mandatory documents
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Scope to get only optional documents
     */
    public function scopeOptional($query)
    {
        return $query->where('is_mandatory', false);
    }

    /**
     * Scope to get only active documents
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->orderBy('display_order');
    }

    /**
     * Scope by category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
