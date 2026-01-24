<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingAssessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'batch_id',
        'class_id',
        'trainer_id',
        'assessment_date',
        'assessment_type',
        'score',
        'theoretical_score',
        'practical_score',
        'total_score',
        'total_marks',
        'max_score',
        'pass_score',
        'result',
        'grade',
        'assessment_location',
        'remedial_needed',
        'remarks',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'score' => 'float',
        'total_marks' => 'float',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function trainingClass()
    {
        return $this->belongsTo(TrainingClass::class, 'class_id');
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class, 'trainer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
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