<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuccessStory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'departure_id',
        'written_note',
        'evidence_type',
        'evidence_path',
        'evidence_filename',
        'is_featured',
        'recorded_by',
        'recorded_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    /**
     * Get the candidate
     */
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Get the departure record
     */
    public function departure()
    {
        return $this->belongsTo(Departure::class);
    }

    /**
     * Get the user who recorded the story
     */
    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope to get only featured stories
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
