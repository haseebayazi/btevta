<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'duration_days',
        'training_type',
        'program_id',
        'is_active',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the program for this course
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get candidates enrolled in this course
     */
    public function candidates()
    {
        return $this->belongsToMany(Candidate::class, 'candidate_courses')
            ->withPivot('start_date', 'end_date', 'status', 'assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    /**
     * Get candidate course assignments
     */
    public function candidateCourses()
    {
        return $this->hasMany(CandidateCourse::class);
    }

    /**
     * Scope to get only active courses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by training type
     */
    public function scopeTrainingType($query, $type)
    {
        return $query->where('training_type', $type);
    }
}
