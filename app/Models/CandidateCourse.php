<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateCourse extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'course_id',
        'start_date',
        'end_date',
        'status',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the candidate
     */
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Get the course
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the user who assigned the course
     */
    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Check if course is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Scope to get only active assignments
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['assigned', 'in_progress']);
    }
}
