<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campus extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'contact_person',
        'phone',
        'email',
        'is_active',
        'created_by',
        'updated_by'
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

    public function departures()
    {
        return $this->hasManyThrough(Departure::class, Candidate::class);
    }

    public function visaProcesses()
    {
        return $this->hasManyThrough(VisaProcess::class, Candidate::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $term)
    {
        // Escape special LIKE characters to prevent SQL LIKE injection
        $escapedTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return $query->where(function($q) use ($escapedTerm) {
            $q->where('name', 'like', "%{$escapedTerm}%")
              ->orWhere('code', 'like', "%{$escapedTerm}%")
              ->orWhere('city', 'like', "%{$escapedTerm}%")
              ->orWhere('contact_person', 'like', "%{$escapedTerm}%");
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}