# Module 4: Training Management Enhancement - Implementation Prompt for Claude

**Project:** BTEVTA WASL
**Module:** Module 4 - Training Management (Enhancement)
**Status:** Existing Module - Requires Modifications
**Date:** February 2026

---

## Executive Summary

Module 4 (Training) **ALREADY EXISTS** with comprehensive functionality including:
- 7 training modules (Orientation, Technical Theory, Practical Workshop, Soft Skills, Cultural Orientation, Language Training, Final Assessment)
- 4 assessment types (Initial, Midterm, Practical, Final)
- 80% attendance threshold enforcement
- Automatic certificate generation with unique numbers

This prompt focuses on **ENHANCEMENTS** for dual-status tracking (Technical vs Soft Skills) and improved assessment workflow.

**CRITICAL:** Existing functionality is working. Make surgical changes only.

---

## Pre-Implementation Analysis

### Step 1: Read Existing Implementation

```
# Controllers (19 methods)
app/Http/Controllers/TrainingController.php

# Services (28 methods)
app/Services/TrainingService.php

# Models
app/Models/Training.php
app/Models/TrainingAttendance.php
app/Models/TrainingAssessment.php
app/Models/TrainingCertificate.php
app/Models/TrainingClass.php
app/Models/TrainingSchedule.php
app/Models/Batch.php

# Views
resources/views/training/

# Tests
tests/Feature/TrainingControllerTest.php
tests/Unit/TrainingServiceTest.php
```

### Step 2: Understand Current Flow

Current:
1. Candidates assigned to batch at registration (moved from training in Module 3)
2. Training started for batch
3. Daily attendance recorded
4. Assessments recorded (Initial, Midterm, Practical, Final)
5. Certificate generated when complete

New requirements ADD dual-status tracking.

---

## Required Changes (from WASL_CHANGE_IMPACT_ANALYSIS.md)

| Change ID | Type | Description | Priority |
|-----------|------|-------------|----------|
| TM-001 | REMOVED | "Active Batch" concept removed | HIGH |
| TM-002 | EXISTS | Interim Assessment tracking | N/A |
| TM-003 | EXISTS | Final Assessment tracking | N/A |
| TM-004 | MODIFIED | Assessment results & evidence upload (enhance) | MEDIUM |
| TM-005 | NEW | Technical Training Status breakdown | HIGH |
| TM-006 | NEW | Soft Skills Training Status breakdown | HIGH |
| TM-007 | MODIFIED | Completion logic: Both must complete | HIGH |

---

## Phase 1: Database Changes

### 1.1 Add Dual Status to Trainings Table

```php
// database/migrations/YYYY_MM_DD_add_dual_status_to_trainings.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            if (!Schema::hasColumn('trainings', 'technical_training_status')) {
                $table->enum('technical_training_status', ['not_started', 'in_progress', 'completed'])
                    ->default('not_started')
                    ->after('status');
            }
            if (!Schema::hasColumn('trainings', 'soft_skills_status')) {
                $table->enum('soft_skills_status', ['not_started', 'in_progress', 'completed'])
                    ->default('not_started')
                    ->after('technical_training_status');
            }
            if (!Schema::hasColumn('trainings', 'technical_completed_at')) {
                $table->timestamp('technical_completed_at')->nullable()->after('soft_skills_status');
            }
            if (!Schema::hasColumn('trainings', 'soft_skills_completed_at')) {
                $table->timestamp('soft_skills_completed_at')->nullable()->after('technical_completed_at');
            }

            $table->index('technical_training_status');
            $table->index('soft_skills_status');
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn([
                'technical_training_status',
                'soft_skills_status',
                'technical_completed_at',
                'soft_skills_completed_at',
            ]);
        });
    }
};
```

### 1.2 Enhance Training Assessments Table

```php
// database/migrations/YYYY_MM_DD_enhance_training_assessments.php
Schema::table('training_assessments', function (Blueprint $table) {
    if (!Schema::hasColumn('training_assessments', 'training_type')) {
        $table->enum('training_type', ['technical', 'soft_skills', 'both'])
            ->default('both')
            ->after('assessment_type');
    }
    if (!Schema::hasColumn('training_assessments', 'evidence_path')) {
        $table->string('evidence_path', 500)->nullable()->after('notes');
    }
    if (!Schema::hasColumn('training_assessments', 'grade')) {
        $table->string('grade', 5)->nullable()->after('score'); // A, B, C, D, F
    }
    if (!Schema::hasColumn('training_assessments', 'max_score')) {
        $table->decimal('max_score', 5, 2)->default(100)->after('grade');
    }

    $table->index('training_type');
});
```

---

## Phase 2: Create/Update Enums

### 2.1 TrainingProgress Enum

```php
// app/Enums/TrainingProgress.php
<?php

namespace App\Enums;

enum TrainingProgress: string
{
    case NOT_STARTED = 'not_started';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match($this) {
            self::NOT_STARTED => 'Not Started',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NOT_STARTED => 'secondary',
            self::IN_PROGRESS => 'warning',
            self::COMPLETED => 'success',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::NOT_STARTED => 'fas fa-clock',
            self::IN_PROGRESS => 'fas fa-spinner fa-spin',
            self::COMPLETED => 'fas fa-check-circle',
        };
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
```

### 2.2 AssessmentType Enum (if not exists)

```php
// app/Enums/AssessmentType.php
<?php

namespace App\Enums;

enum AssessmentType: string
{
    case INITIAL = 'initial';
    case INTERIM = 'interim';
    case MIDTERM = 'midterm';
    case PRACTICAL = 'practical';
    case FINAL = 'final';

    public function label(): string
    {
        return match($this) {
            self::INITIAL => 'Initial Assessment',
            self::INTERIM => 'Interim Assessment',
            self::MIDTERM => 'Midterm Assessment',
            self::PRACTICAL => 'Practical Assessment',
            self::FINAL => 'Final Assessment',
        };
    }

    public function isRequired(): bool
    {
        return in_array($this, [self::MIDTERM, self::FINAL]);
    }

    public static function technicalTypes(): array
    {
        return [self::INITIAL, self::MIDTERM, self::PRACTICAL, self::FINAL];
    }

    public static function softSkillsTypes(): array
    {
        return [self::INTERIM, self::FINAL];
    }
}
```

---

## Phase 3: Update Models

### 3.1 Update Training Model

Add to `app/Models/Training.php`:

```php
use App\Enums\TrainingProgress;

// Add to $fillable:
'technical_training_status',
'soft_skills_status',
'technical_completed_at',
'soft_skills_completed_at',

// Add to $casts:
'technical_training_status' => TrainingProgress::class,
'soft_skills_status' => TrainingProgress::class,
'technical_completed_at' => 'datetime',
'soft_skills_completed_at' => 'datetime',

// Add helper methods:

/**
 * Get overall completion percentage
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

/**
 * Check if both training types are complete
 */
public function isBothComplete(): bool
{
    return $this->technical_training_status === TrainingProgress::COMPLETED
        && $this->soft_skills_status === TrainingProgress::COMPLETED;
}

/**
 * Start technical training
 */
public function startTechnicalTraining(): void
{
    if ($this->technical_training_status === TrainingProgress::NOT_STARTED) {
        $this->update(['technical_training_status' => TrainingProgress::IN_PROGRESS]);

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->log('Technical training started');
    }
}

/**
 * Complete technical training
 */
public function completeTechnicalTraining(): void
{
    // Check if required technical assessments are passed
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
 * Start soft skills training
 */
public function startSoftSkillsTraining(): void
{
    if ($this->soft_skills_status === TrainingProgress::NOT_STARTED) {
        $this->update(['soft_skills_status' => TrainingProgress::IN_PROGRESS]);

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->log('Soft skills training started');
    }
}

/**
 * Complete soft skills training
 */
public function completeSoftSkillsTraining(): void
{
    // Check if required soft skills assessments are passed
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

/**
 * Check if required technical assessments are passed
 */
public function hasPassedTechnicalAssessments(): bool
{
    // Must have at least midterm OR practical, and final
    $midtermOrPractical = $this->assessments()
        ->whereIn('assessment_type', ['midterm', 'practical'])
        ->where('training_type', 'technical')
        ->where('score', '>=', 50)
        ->exists();

    $final = $this->assessments()
        ->where('assessment_type', 'final')
        ->where('training_type', 'technical')
        ->where('score', '>=', 50)
        ->exists();

    return $midtermOrPractical && $final;
}

/**
 * Check if required soft skills assessments are passed
 */
public function hasPassedSoftSkillsAssessments(): bool
{
    return $this->assessments()
        ->where('assessment_type', 'final')
        ->where('training_type', 'soft_skills')
        ->where('score', '>=', 50)
        ->exists();
}

/**
 * Check overall completion and update candidate status
 */
protected function checkOverallCompletion(): void
{
    if ($this->isBothComplete()) {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Update candidate status
        $this->candidate->update(['status' => 'training_completed']);

        // Fire event for certificate generation
        event(new \App\Events\TrainingCompleted($this, $this->candidate));
    }
}

/**
 * Get technical assessments
 */
public function technicalAssessments()
{
    return $this->assessments()->where('training_type', 'technical');
}

/**
 * Get soft skills assessments
 */
public function softSkillsAssessments()
{
    return $this->assessments()->where('training_type', 'soft_skills');
}
```

### 3.2 Update TrainingAssessment Model

Add to `app/Models/TrainingAssessment.php`:

```php
// Add to $fillable:
'training_type',
'evidence_path',
'grade',
'max_score',

// Add to $casts:
'max_score' => 'decimal:2',

// Add to $hidden (security):
'evidence_path',

// Add constants:
const GRADE_A = 'A';
const GRADE_B = 'B';
const GRADE_C = 'C';
const GRADE_D = 'D';
const GRADE_F = 'F';

// Add methods:

/**
 * Calculate grade from score
 */
public static function calculateGrade(float $score, float $maxScore = 100): string
{
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
 * Get percentage score
 */
public function getPercentageAttribute(): float
{
    if ($this->max_score <= 0) return 0;
    return round(($this->score / $this->max_score) * 100, 2);
}

/**
 * Check if passed (50% or above)
 */
public function isPassed(): bool
{
    return $this->percentage >= 50;
}

/**
 * Get evidence URL
 */
public function getEvidenceUrlAttribute(): ?string
{
    if (empty($this->evidence_path)) {
        return null;
    }
    return route('secure-file.view', ['path' => $this->evidence_path]);
}

/**
 * Upload evidence file
 */
public function uploadEvidence($file): string
{
    if (!$file || !$file->isValid()) {
        throw new \Exception('Invalid file provided');
    }

    // Delete old evidence if exists
    if ($this->evidence_path) {
        \Storage::disk('private')->delete($this->evidence_path);
    }

    $trainingId = $this->training_id;
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

// Boot method for auto-calculating grade
protected static function boot()
{
    parent::boot();

    static::saving(function ($assessment) {
        if ($assessment->score !== null && $assessment->max_score > 0) {
            $assessment->grade = self::calculateGrade($assessment->score, $assessment->max_score);
        }
    });
}
```

---

## Phase 4: Update Training Service

Add/modify methods in `app/Services/TrainingService.php`:

```php
/**
 * Record assessment with training type
 */
public function recordAssessmentWithType(
    Training $training,
    Candidate $candidate,
    string $assessmentType,
    string $trainingType,
    float $score,
    float $maxScore = 100,
    ?string $notes = null,
    $evidenceFile = null
): TrainingAssessment {
    return DB::transaction(function () use ($training, $candidate, $assessmentType, $trainingType, $score, $maxScore, $notes, $evidenceFile) {

        // Check if assessment already exists
        $existing = TrainingAssessment::where('training_id', $training->id)
            ->where('candidate_id', $candidate->id)
            ->where('assessment_type', $assessmentType)
            ->where('training_type', $trainingType)
            ->first();

        if ($existing) {
            throw new \Exception("Assessment already recorded for this type.");
        }

        $assessment = TrainingAssessment::create([
            'training_id' => $training->id,
            'candidate_id' => $candidate->id,
            'assessment_type' => $assessmentType,
            'training_type' => $trainingType,
            'score' => $score,
            'max_score' => $maxScore,
            'notes' => $notes,
            'assessed_by' => auth()->id(),
            'assessed_at' => now(),
        ]);

        // Handle evidence upload
        if ($evidenceFile) {
            $assessment->uploadEvidence($evidenceFile);
        }

        // Auto-start the corresponding training type if not started
        if ($trainingType === 'technical' && $training->technical_training_status->value === 'not_started') {
            $training->startTechnicalTraining();
        } elseif ($trainingType === 'soft_skills' && $training->soft_skills_status->value === 'not_started') {
            $training->startSoftSkillsTraining();
        }

        activity()
            ->performedOn($training)
            ->causedBy(auth()->user())
            ->withProperties([
                'candidate_id' => $candidate->id,
                'assessment_type' => $assessmentType,
                'training_type' => $trainingType,
                'score' => $score,
                'max_score' => $maxScore,
                'grade' => $assessment->grade,
            ])
            ->log("Assessment recorded: {$trainingType} - {$assessmentType}");

        return $assessment;
    });
}

/**
 * Complete specific training type
 */
public function completeTrainingType(Training $training, string $trainingType): void
{
    if ($trainingType === 'technical') {
        $training->completeTechnicalTraining();
    } elseif ($trainingType === 'soft_skills') {
        $training->completeSoftSkillsTraining();
    } else {
        throw new \Exception("Invalid training type: {$trainingType}");
    }
}

/**
 * Get training progress summary
 */
public function getTrainingProgress(Training $training): array
{
    $technicalAssessments = $training->technicalAssessments()->get();
    $softSkillsAssessments = $training->softSkillsAssessments()->get();

    return [
        'technical' => [
            'status' => $training->technical_training_status,
            'status_label' => $training->technical_training_status->label(),
            'status_color' => $training->technical_training_status->color(),
            'completed_at' => $training->technical_completed_at?->format('Y-m-d H:i'),
            'assessments' => $technicalAssessments->map(fn($a) => [
                'type' => $a->assessment_type,
                'score' => $a->score,
                'max_score' => $a->max_score,
                'percentage' => $a->percentage,
                'grade' => $a->grade,
                'passed' => $a->isPassed(),
            ]),
            'can_complete' => $training->hasPassedTechnicalAssessments(),
        ],
        'soft_skills' => [
            'status' => $training->soft_skills_status,
            'status_label' => $training->soft_skills_status->label(),
            'status_color' => $training->soft_skills_status->color(),
            'completed_at' => $training->soft_skills_completed_at?->format('Y-m-d H:i'),
            'assessments' => $softSkillsAssessments->map(fn($a) => [
                'type' => $a->assessment_type,
                'score' => $a->score,
                'max_score' => $a->max_score,
                'percentage' => $a->percentage,
                'grade' => $a->grade,
                'passed' => $a->isPassed(),
            ]),
            'can_complete' => $training->hasPassedSoftSkillsAssessments(),
        ],
        'overall' => [
            'completion_percentage' => $training->completion_percentage,
            'both_complete' => $training->isBothComplete(),
            'can_generate_certificate' => $training->isBothComplete(),
        ],
    ];
}

/**
 * Get batch training summary with dual status
 */
public function getBatchTrainingSummary(Batch $batch): array
{
    $trainings = Training::where('batch_id', $batch->id)
        ->with(['candidate', 'assessments'])
        ->get();

    return [
        'total_candidates' => $trainings->count(),
        'technical' => [
            'not_started' => $trainings->where('technical_training_status.value', 'not_started')->count(),
            'in_progress' => $trainings->where('technical_training_status.value', 'in_progress')->count(),
            'completed' => $trainings->where('technical_training_status.value', 'completed')->count(),
        ],
        'soft_skills' => [
            'not_started' => $trainings->where('soft_skills_status.value', 'not_started')->count(),
            'in_progress' => $trainings->where('soft_skills_status.value', 'in_progress')->count(),
            'completed' => $trainings->where('soft_skills_status.value', 'completed')->count(),
        ],
        'fully_complete' => $trainings->filter(fn($t) => $t->isBothComplete())->count(),
        'candidates' => $trainings->map(fn($t) => [
            'candidate_id' => $t->candidate_id,
            'candidate_name' => $t->candidate->name,
            'technical_status' => $t->technical_training_status->value,
            'soft_skills_status' => $t->soft_skills_status->value,
            'completion_percentage' => $t->completion_percentage,
        ]),
    ];
}
```

---

## Phase 5: Update Training Controller

Add/modify methods in `app/Http/Controllers/TrainingController.php`:

```php
/**
 * Show dual status training dashboard
 */
public function dualStatusDashboard(Batch $batch)
{
    $this->authorize('view', $batch);

    $service = app(TrainingService::class);
    $summary = $service->getBatchTrainingSummary($batch);

    $trainings = Training::where('batch_id', $batch->id)
        ->with(['candidate', 'assessments'])
        ->get();

    return view('training.dual-status-dashboard', compact('batch', 'summary', 'trainings'));
}

/**
 * Show training progress for a candidate
 */
public function candidateProgress(Training $training)
{
    $this->authorize('view', $training);

    $service = app(TrainingService::class);
    $progress = $service->getTrainingProgress($training);

    return view('training.candidate-progress', compact('training', 'progress'));
}

/**
 * Record assessment with training type
 */
public function storeTypedAssessment(Request $request, Training $training)
{
    $this->authorize('update', $training);

    $validated = $request->validate([
        'candidate_id' => 'required|exists:candidates,id',
        'assessment_type' => 'required|in:initial,interim,midterm,practical,final',
        'training_type' => 'required|in:technical,soft_skills',
        'score' => 'required|numeric|min:0|max:100',
        'max_score' => 'required|numeric|min:1|max:100',
        'notes' => 'nullable|string|max:1000',
        'evidence' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
    ]);

    try {
        $candidate = Candidate::findOrFail($validated['candidate_id']);
        $service = app(TrainingService::class);

        $assessment = $service->recordAssessmentWithType(
            $training,
            $candidate,
            $validated['assessment_type'],
            $validated['training_type'],
            $validated['score'],
            $validated['max_score'],
            $validated['notes'] ?? null,
            $request->file('evidence')
        );

        return back()->with('success',
            "Assessment recorded. Grade: {$assessment->grade} ({$assessment->percentage}%)"
        );

    } catch (\Exception $e) {
        return back()->withInput()->with('error', $e->getMessage());
    }
}

/**
 * Complete training type for a candidate
 */
public function completeTrainingType(Request $request, Training $training)
{
    $this->authorize('update', $training);

    $validated = $request->validate([
        'training_type' => 'required|in:technical,soft_skills',
    ]);

    try {
        $service = app(TrainingService::class);
        $service->completeTrainingType($training, $validated['training_type']);

        $typeLabel = $validated['training_type'] === 'technical' ? 'Technical' : 'Soft Skills';

        if ($training->fresh()->isBothComplete()) {
            return back()->with('success',
                "{$typeLabel} training completed. All training complete - certificate can be generated!"
            );
        }

        return back()->with('success', "{$typeLabel} training marked as completed.");

    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}
```

---

## Phase 6: Add Routes

Add to `routes/web.php`:

```php
// Training dual status routes
Route::middleware(['auth'])->prefix('training')->name('training.')->group(function () {
    Route::get('/batch/{batch}/dual-status', [TrainingController::class, 'dualStatusDashboard'])
        ->name('dual-status');
    Route::get('/{training}/progress', [TrainingController::class, 'candidateProgress'])
        ->name('candidate-progress');
    Route::post('/{training}/assessment/typed', [TrainingController::class, 'storeTypedAssessment'])
        ->name('assessment.typed');
    Route::post('/{training}/complete-type', [TrainingController::class, 'completeTrainingType'])
        ->name('complete-type');
});
```

---

## Phase 7: Create Views

### 7.1 Dual Status Dashboard

Create `resources/views/training/dual-status-dashboard.blade.php`:

**Required Sections:**
1. Batch Info Header
2. Summary Stats Cards:
   - Technical Training (Not Started / In Progress / Completed)
   - Soft Skills Training (Not Started / In Progress / Completed)
   - Fully Complete count
3. Candidates Table with dual status columns
4. Progress bars for each candidate

### 7.2 Candidate Progress View

Create `resources/views/training/candidate-progress.blade.php`:

**Required Sections:**
1. Candidate Info Header
2. Technical Training Card:
   - Status badge
   - List of assessments with scores/grades
   - Complete button (if can_complete)
3. Soft Skills Training Card:
   - Status badge
   - List of assessments with scores/grades
   - Complete button (if can_complete)
4. Overall Progress:
   - Combined progress bar
   - Certificate generation button (if both complete)

### 7.3 Assessment Form with Training Type

Create `resources/views/training/partials/typed-assessment-form.blade.php`:

**Required Fields:**
- Training Type (Technical / Soft Skills)
- Assessment Type (dropdown based on training type)
- Score
- Max Score (default 100)
- Notes
- Evidence Upload

---

## Phase 8: Data Migration for Existing Records

```php
// database/migrations/YYYY_MM_DD_migrate_existing_training_status.php
public function up(): void
{
    // Set dual status based on existing overall status
    DB::statement("
        UPDATE trainings
        SET
            technical_training_status = CASE
                WHEN status = 'completed' THEN 'completed'
                WHEN status IN ('in_progress', 'active') THEN 'in_progress'
                ELSE 'not_started'
            END,
            soft_skills_status = CASE
                WHEN status = 'completed' THEN 'completed'
                WHEN status IN ('in_progress', 'active') THEN 'in_progress'
                ELSE 'not_started'
            END,
            technical_completed_at = CASE
                WHEN status = 'completed' THEN completed_at
                ELSE NULL
            END,
            soft_skills_completed_at = CASE
                WHEN status = 'completed' THEN completed_at
                ELSE NULL
            END
    ");

    // Set training_type for existing assessments
    DB::statement("
        UPDATE training_assessments
        SET training_type = CASE
            WHEN assessment_type IN ('initial', 'midterm', 'practical', 'final') THEN 'technical'
            ELSE 'both'
        END
        WHERE training_type IS NULL
    ");
}
```

---

## Phase 9: Testing

### 9.1 Unit Tests

```php
// tests/Unit/TrainingDualStatusTest.php
public function test_technical_training_can_be_started()
public function test_soft_skills_training_can_be_started()
public function test_cannot_complete_technical_without_assessments()
public function test_cannot_complete_soft_skills_without_assessments()
public function test_overall_completion_requires_both()
public function test_grade_calculation_correct()
public function test_completion_percentage_calculation()
public function test_certificate_only_when_both_complete()
```

### 9.2 Feature Tests

```php
// tests/Feature/TrainingDualStatusTest.php
public function test_dual_status_dashboard_loads()
public function test_can_record_technical_assessment()
public function test_can_record_soft_skills_assessment()
public function test_can_complete_technical_training()
public function test_can_complete_soft_skills_training()
public function test_candidate_status_updates_when_both_complete()
public function test_evidence_upload_works()
```

---

## Validation Checklist

After implementation, verify:

- [ ] Dual status columns exist in trainings table
- [ ] Training type column exists in assessments table
- [ ] TrainingProgress enum created
- [ ] Training model has dual status methods
- [ ] Assessment model has evidence upload and grade calculation
- [ ] Service methods for typed assessments work
- [ ] Controller methods added
- [ ] Routes working
- [ ] Dual status dashboard shows correct stats
- [ ] Technical assessments recorded correctly
- [ ] Soft skills assessments recorded correctly
- [ ] Cannot complete without required assessments
- [ ] Both complete triggers certificate eligibility
- [ ] Candidate status updates correctly
- [ ] Existing data migrated properly
- [ ] All tests pass

---

## Files to Create

```
app/Enums/TrainingProgress.php
app/Enums/AssessmentType.php
database/migrations/YYYY_MM_DD_add_dual_status_to_trainings.php
database/migrations/YYYY_MM_DD_enhance_training_assessments.php
database/migrations/YYYY_MM_DD_migrate_existing_training_status.php
resources/views/training/dual-status-dashboard.blade.php
resources/views/training/candidate-progress.blade.php
resources/views/training/partials/typed-assessment-form.blade.php
tests/Unit/TrainingDualStatusTest.php
tests/Feature/TrainingDualStatusTest.php
docs/MODULE_4_TRAINING.md
```

## Files to Modify

```
app/Models/Training.php
app/Models/TrainingAssessment.php
app/Services/TrainingService.php
app/Http/Controllers/TrainingController.php
routes/web.php
CLAUDE.md
README.md
```

---

## Success Criteria

Module 4 Enhancement is complete when:

1. Dual status tracking (Technical/Soft Skills) works
2. Assessments categorized by training type
3. Cannot complete training type without required assessments
4. Overall completion requires both types complete
5. Certificate only available when both complete
6. Evidence upload for assessments works
7. Grades auto-calculated
8. Existing data migrated correctly
9. All tests pass
10. No regression in existing training functionality

---

*End of Module 4 Implementation Prompt*
