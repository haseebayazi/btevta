<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'created_by',
        'updated_by'
    ];

    /**
     * Get documents with this tag
     */
    public function documents()
    {
        return $this->belongsToMany(DocumentArchive::class, 'document_tag_pivot', 'tag_id', 'document_id')
            ->withTimestamps();
    }

    /**
     * Get the creator of the tag
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the last updater of the tag
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to search tags by name
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
            ->orWhere('slug', 'like', "%{$search}%");
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (auth()->check()) {
                $tag->created_by = auth()->id();
                $tag->updated_by = auth()->id();
            }

            // Auto-generate slug if not provided
            if (empty($tag->slug)) {
                $tag->slug = \Str::slug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if (auth()->check()) {
                $tag->updated_by = auth()->id();
            }
        });
    }
}
