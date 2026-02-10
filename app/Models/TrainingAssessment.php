<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class TrainingAssessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'training_id',
        'candidate_id',
        'batch_id',
        'class_id',
        'trainer_id',
        'assessment_date',
        'assessment_type',
        'training_type',
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
        'evidence_path',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'score' => 'float',
        'total_marks' => 'float',
        'max_score' => 'float',
    ];

    protected $hidden = [
        'evidence_path',
    ];

    const GRADE_A = 'A';
    const GRADE_B = 'B';
    const GRADE_C = 'C';
    const GRADE_D = 'D';
    const GRADE_F = 'F';

    // ==================== RELATIONSHIPS ====================

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

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

    // ==================== GRADE CALCULATION ====================

    /**
     * Calculate grade from score.
     */
    public static function calculateGrade(float $score, float $maxScore = 100): string
    {
        if ($maxScore <= 0) {
            return self::GRADE_F;
        }

        $percentage = ($score / $maxScore) * 100;

        return match(true) {
            $percentage >= 90 => self::GRADE_A,
            $percentage >= 80 => self::GRADE_B,
            $percentage >= 70 => self::GRADE_C,
            $percentage >= 50 => self::GRADE_D,
            default => self::GRADE_F,
        };
    }

    /**
     * Get percentage score.
     */
    public function getPercentageAttribute(): float
    {
        $max = $this->max_score ?: $this->total_marks ?: 100;
        $score = $this->score ?: $this->total_score ?: 0;
        if ($max <= 0) {
            return 0;
        }
        return round(($score / $max) * 100, 2);
    }

    /**
     * Check if passed (50% or above).
     */
    public function isPassed(): bool
    {
        if ($this->result) {
            return $this->result === 'pass';
        }
        return $this->percentage >= 50;
    }

    // ==================== EVIDENCE UPLOAD ====================

    /**
     * Get evidence URL.
     */
    public function getEvidenceUrlAttribute(): ?string
    {
        if (empty($this->evidence_path)) {
            return null;
        }
        return route('secure-file.view', ['path' => $this->evidence_path]);
    }

    /**
     * Upload evidence file.
     */
    public function uploadEvidence($file): string
    {
        if (!$file || !$file->isValid()) {
            throw new \Exception('Invalid file provided');
        }

        // Delete old evidence if exists
        if ($this->evidence_path) {
            Storage::disk('private')->delete($this->evidence_path);
        }

        $trainingId = $this->training_id ?? 0;
        $candidateId = $this->candidate_id;
        $type = $this->assessment_type;
        $timestamp = now()->format('Y-m-d_His');

        $extension = $file->getClientOriginalExtension();
        $filename = "assessment_{$candidateId}_{$type}_{$timestamp}.{$extension}";

        $path = $file->storeAs(
            "training/assessments/{$trainingId}",
            $filename,
            'private'
        );

        $this->evidence_path = $path;
        $this->save();

        return $path;
    }

    // ==================== BOOT ====================

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($assessment) {
            // Auto-calculate grade from score
            $score = $assessment->score ?: $assessment->total_score ?: 0;
            $maxScore = $assessment->max_score ?: $assessment->total_marks ?: 100;

            if ($maxScore > 0 && empty($assessment->grade)) {
                $assessment->grade = self::calculateGrade($score, $maxScore);
            }
        });

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
