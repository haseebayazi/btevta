<?php

namespace App\Models;

use App\Enums\TrainingProgress;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Training extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'batch_id',
        'status',
        'technical_training_status',
        'soft_skills_status',
        'technical_completed_at',
        'soft_skills_completed_at',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'technical_training_status' => TrainingProgress::class,
        'soft_skills_status' => TrainingProgress::class,
        'technical_completed_at' => 'datetime',
        'soft_skills_completed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function assessments()
    {
        return $this->hasMany(TrainingAssessment::class);
    }

    public function technicalAssessments()
    {
        return $this->assessments()->where('training_type', 'technical');
    }

    public function softSkillsAssessments()
    {
        return $this->assessments()->where('training_type', 'soft_skills');
    }

    // ==================== COMPUTED ATTRIBUTES ====================

    /**
     * Get overall completion percentage.
     */
    public function getCompletionPercentageAttribute(): int
    {
        $techComplete = $this->technical_training_status === TrainingProgress::COMPLETED ? 50 : 0;
        $softComplete = $this->soft_skills_status === TrainingProgress::COMPLETED ? 50 : 0;

        if ($this->technical_training_status === TrainingProgress::IN_PROGRESS) {
            $techComplete = 25;
        }
        if ($this->soft_skills_status === TrainingProgress::IN_PROGRESS) {
            $softComplete = 25;
        }

        return $techComplete + $softComplete;
    }

    // ==================== STATUS METHODS ====================

    /**
     * Check if both training types are complete.
     */
    public function isBothComplete(): bool
    {
        return $this->technical_training_status === TrainingProgress::COMPLETED
            && $this->soft_skills_status === TrainingProgress::COMPLETED;
    }

    /**
     * Start technical training.
     */
    public function startTechnicalTraining(): void
    {
        if ($this->technical_training_status === TrainingProgress::NOT_STARTED) {
            $this->update([
                'technical_training_status' => TrainingProgress::IN_PROGRESS,
                'status' => 'in_progress',
            ]);

            activity()
                ->performedOn($this)
                ->causedBy(auth()->user())
                ->log('Technical training started');
        }
    }

    /**
     * Complete technical training.
     */
    public function completeTechnicalTraining(): void
    {
        if (!$this->hasPassedTechnicalAssessments()) {
            throw new \Exception('Required technical assessments not completed or passed.');
        }

        $this->update([
            'technical_training_status' => TrainingProgress::COMPLETED,
            'technical_completed_at' => now(),
        ]);

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->log('Technical training completed');

        $this->checkOverallCompletion();
    }

    /**
     * Start soft skills training.
     */
    public function startSoftSkillsTraining(): void
    {
        if ($this->soft_skills_status === TrainingProgress::NOT_STARTED) {
            $this->update([
                'soft_skills_status' => TrainingProgress::IN_PROGRESS,
                'status' => 'in_progress',
            ]);

            activity()
                ->performedOn($this)
                ->causedBy(auth()->user())
                ->log('Soft skills training started');
        }
    }

    /**
     * Complete soft skills training.
     */
    public function completeSoftSkillsTraining(): void
    {
        if (!$this->hasPassedSoftSkillsAssessments()) {
            throw new \Exception('Required soft skills assessments not completed or passed.');
        }

        $this->update([
            'soft_skills_status' => TrainingProgress::COMPLETED,
            'soft_skills_completed_at' => now(),
        ]);

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->log('Soft skills training completed');

        $this->checkOverallCompletion();
    }

    // ==================== ASSESSMENT CHECKS ====================

    /**
     * Check if required technical assessments are passed.
     * Must have at least midterm OR practical, and final.
     */
    public function hasPassedTechnicalAssessments(): bool
    {
        $midtermOrPractical = $this->assessments()
            ->whereIn('assessment_type', ['midterm', 'practical'])
            ->where('training_type', 'technical')
            ->where(function ($q) {
                $q->where('result', 'pass')
                  ->orWhere('score', '>=', 50);
            })
            ->exists();

        $final = $this->assessments()
            ->where('assessment_type', 'final')
            ->where('training_type', 'technical')
            ->where(function ($q) {
                $q->where('result', 'pass')
                  ->orWhere('score', '>=', 50);
            })
            ->exists();

        return $midtermOrPractical && $final;
    }

    /**
     * Check if required soft skills assessments are passed.
     */
    public function hasPassedSoftSkillsAssessments(): bool
    {
        return $this->assessments()
            ->where('assessment_type', 'final')
            ->where('training_type', 'soft_skills')
            ->where(function ($q) {
                $q->where('result', 'pass')
                  ->orWhere('score', '>=', 50);
            })
            ->exists();
    }

    // ==================== COMPLETION LOGIC ====================

    /**
     * Check overall completion and update candidate status.
     */
    protected function checkOverallCompletion(): void
    {
        if ($this->isBothComplete()) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Update candidate status to training_completed
            if ($this->candidate) {
                $this->candidate->update([
                    'training_status' => 'completed',
                ]);
            }

            // Fire event for certificate generation
            event(new \App\Events\TrainingCompleted($this, $this->candidate));
        }
    }

    // ==================== FACTORY / BOOT ====================

    /**
     * Get or create a Training record for a candidate.
     */
    public static function findOrCreateForCandidate(Candidate $candidate): self
    {
        return self::firstOrCreate(
            ['candidate_id' => $candidate->id],
            [
                'batch_id' => $candidate->batch_id,
                'status' => 'not_started',
                'technical_training_status' => TrainingProgress::NOT_STARTED,
                'soft_skills_status' => TrainingProgress::NOT_STARTED,
            ]
        );
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
