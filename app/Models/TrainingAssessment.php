<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingAssessment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'candidate_id', 'batch_id', 'assessment_date', 'assessment_type',
        'score', 'total_marks', 'grade', 'remarks'
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
}