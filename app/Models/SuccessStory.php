<?php

namespace App\Models;

use App\Enums\StoryStatus;
use App\Enums\StoryType;
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
        // Enhanced fields
        'story_type',
        'headline',
        'employer_name',
        'position_achieved',
        'country_id',
        'salary_achieved',
        'salary_currency',
        'employment_start_date',
        'time_to_employment_days',
        'views_count',
        'published_at',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'is_featured'           => 'boolean',
        'recorded_at'           => 'datetime',
        'story_type'            => StoryType::class,
        'status'                => StoryStatus::class,
        'salary_achieved'       => 'decimal:2',
        'employment_start_date' => 'date',
        'published_at'          => 'datetime',
        'approved_at'           => 'datetime',
        'views_count'           => 'integer',
        'time_to_employment_days' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function departure()
    {
        return $this->belongsTo(Departure::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function evidence()
    {
        return $this->hasMany(SuccessStoryEvidence::class)->orderBy('display_order');
    }

    public function primaryEvidence()
    {
        return $this->hasOne(SuccessStoryEvidence::class)->where('is_primary', true);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePublished($query)
    {
        return $query->where('status', StoryStatus::PUBLISHED->value);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('story_type', $type);
    }

    // -------------------------------------------------------------------------
    // Workflow Methods
    // -------------------------------------------------------------------------

    public function submitForReview(): void
    {
        $this->update(['status' => StoryStatus::PENDING_REVIEW]);

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->log('Success story submitted for review');
    }

    public function approve(): void
    {
        $this->update([
            'status'      => StoryStatus::APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->log('Success story approved');
    }

    public function publish(): void
    {
        $this->update([
            'status'       => StoryStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->log('Success story published');
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status'           => StoryStatus::REJECTED,
            'rejection_reason' => $reason,
        ]);

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['reason' => $reason])
            ->log('Success story rejected');
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}
