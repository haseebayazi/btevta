<?php

namespace App\Services;

use App\Models\TrainingAssessment;
use App\Models\Candidate;
use App\Models\TrainingSchedule;
use App\Enums\AssessmentType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TrainingAssessmentService
{
    /**
     * Create a new training assessment.
     *
     * @param array $data
     * @return TrainingAssessment
     * @throws \Exception
     */
    public function createAssessment(array $data): TrainingAssessment
    {
        // Validate required fields
        $this->validateAssessmentData($data);

        DB::beginTransaction();
        try {
            // Handle evidence file upload if present
            if (isset($data['evidence_file'])) {
                $evidencePath = $data['evidence_file']->store(
                    'assessments/' . $data['candidate_id'],
                    'private'
                );
                $data['evidence_path'] = $evidencePath;
                $data['evidence_filename'] = $data['evidence_file']->getClientOriginalName();
                unset($data['evidence_file']);
            }

            // Set assessed by and timestamp
            $data['assessed_by'] = auth()->id();
            $data['assessed_at'] = now();

            // Create the assessment
            $assessment = TrainingAssessment::create($data);

            // Check if training should be marked as completed
            $this->checkTrainingCompletion($assessment->candidate_id);

            DB::commit();

            // Log the assessment creation
            activity()
                ->performedOn($assessment)
                ->causedBy(auth()->user())
                ->withProperties([
                    'assessment_type' => $data['assessment_type'],
                    'score' => $data['score'],
                    'max_score' => $data['max_score'],
                ])
                ->log('Training assessment created');

            return $assessment->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create assessment', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing training assessment.
     *
     * @param TrainingAssessment $assessment
     * @param array $data
     * @return TrainingAssessment
     * @throws \Exception
     */
    public function updateAssessment(TrainingAssessment $assessment, array $data): TrainingAssessment
    {
        DB::beginTransaction();
        try {
            // Handle evidence file upload if present
            if (isset($data['evidence_file'])) {
                // Delete old evidence file
                if ($assessment->evidence_path) {
                    Storage::disk('private')->delete($assessment->evidence_path);
                }

                $evidencePath = $data['evidence_file']->store(
                    'assessments/' . $assessment->candidate_id,
                    'private'
                );
                $data['evidence_path'] = $evidencePath;
                $data['evidence_filename'] = $data['evidence_file']->getClientOriginalName();
                unset($data['evidence_file']);
            }

            // Update the assessment
            $assessment->update($data);

            // Recheck training completion status
            $this->checkTrainingCompletion($assessment->candidate_id);

            DB::commit();

            // Log the update
            activity()
                ->performedOn($assessment)
                ->causedBy(auth()->user())
                ->log('Training assessment updated');

            return $assessment->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update assessment', [
                'assessment_id' => $assessment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a training assessment.
     *
     * @param TrainingAssessment $assessment
     * @return bool
     * @throws \Exception
     */
    public function deleteAssessment(TrainingAssessment $assessment): bool
    {
        DB::beginTransaction();
        try {
            $candidateId = $assessment->candidate_id;

            // Delete evidence file if exists
            if ($assessment->evidence_path) {
                Storage::disk('private')->delete($assessment->evidence_path);
            }

            // Log before deletion
            activity()
                ->performedOn($assessment)
                ->causedBy(auth()->user())
                ->log('Training assessment deleted');

            $assessment->delete();

            // Recheck training completion status
            $this->checkTrainingCompletion($candidateId);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete assessment', [
                'assessment_id' => $assessment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate assessment data.
     *
     * @param array $data
     * @return void
     * @throws \Exception
     */
    protected function validateAssessmentData(array $data): void
    {
        if (!isset($data['candidate_id'])) {
            throw new \Exception('Candidate ID is required.');
        }

        if (!isset($data['assessment_type'])) {
            throw new \Exception('Assessment type is required.');
        }

        if (!isset($data['score']) || !isset($data['max_score'])) {
            throw new \Exception('Score and max score are required.');
        }

        if ($data['score'] > $data['max_score']) {
            throw new \Exception('Score cannot exceed maximum score.');
        }

        // Validate assessment type is valid enum
        try {
            AssessmentType::from($data['assessment_type']);
        } catch (\ValueError $e) {
            throw new \Exception('Invalid assessment type provided.');
        }

        // Check if candidate exists
        if (!Candidate::find($data['candidate_id'])) {
            throw new \Exception('Invalid candidate ID provided.');
        }
    }

    /**
     * Check if training should be marked as completed based on assessments.
     *
     * @param int $candidateId
     * @return void
     */
    protected function checkTrainingCompletion(int $candidateId): void
    {
        $candidate = Candidate::find($candidateId);
        if (!$candidate) {
            return;
        }

        // Get all assessments for this candidate
        $assessments = TrainingAssessment::where('candidate_id', $candidateId)->get();

        $hasInterim = $assessments->where('assessment_type', AssessmentType::INTERIM->value)->isNotEmpty();
        $hasFinal = $assessments->where('assessment_type', AssessmentType::FINAL->value)->isNotEmpty();

        // Get training schedule if exists
        $trainingSchedule = $candidate->trainingSchedules()->first();

        if ($trainingSchedule) {
            // Update training completion based on assessments
            // Training is complete only if both interim and final assessments are done
            if ($hasInterim && $hasFinal) {
                if ($trainingSchedule->technical_training_status !== 'completed') {
                    $trainingSchedule->update([
                        'technical_training_status' => 'completed',
                        'soft_skills_status' => 'completed',
                    ]);

                    // Log training completion
                    activity()
                        ->performedOn($candidate)
                        ->causedBy(auth()->user())
                        ->log('Training marked as completed based on assessments');
                }
            }
        }
    }

    /**
     * Get assessment summary for a candidate.
     *
     * @param int $candidateId
     * @return array
     */
    public function getAssessmentSummary(int $candidateId): array
    {
        $assessments = TrainingAssessment::where('candidate_id', $candidateId)
            ->with('assessor')
            ->orderBy('assessed_at', 'desc')
            ->get();

        $interim = $assessments->where('assessment_type', AssessmentType::INTERIM->value)->first();
        $final = $assessments->where('assessment_type', AssessmentType::FINAL->value)->first();

        return [
            'total_assessments' => $assessments->count(),
            'has_interim' => $interim !== null,
            'has_final' => $final !== null,
            'interim_assessment' => $interim ? [
                'id' => $interim->id,
                'score' => $interim->score,
                'max_score' => $interim->max_score,
                'percentage' => $interim->percentage,
                'assessed_at' => $interim->assessed_at,
                'assessor' => $interim->assessor->name ?? 'N/A',
            ] : null,
            'final_assessment' => $final ? [
                'id' => $final->id,
                'score' => $final->score,
                'max_score' => $final->max_score,
                'percentage' => $final->percentage,
                'assessed_at' => $final->assessed_at,
                'assessor' => $final->assessor->name ?? 'N/A',
            ] : null,
            'is_training_complete' => $interim !== null && $final !== null,
            'average_score_percentage' => $assessments->isNotEmpty()
                ? round($assessments->avg('percentage'), 2)
                : 0,
        ];
    }

    /**
     * Get assessments for a batch.
     *
     * @param int $batchId
     * @param string|null $assessmentType
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBatchAssessments(int $batchId, ?string $assessmentType = null)
    {
        $query = TrainingAssessment::whereHas('candidate', function ($q) use ($batchId) {
            $q->where('batch_id', $batchId);
        })->with(['candidate', 'assessor']);

        if ($assessmentType) {
            $query->where('assessment_type', $assessmentType);
        }

        return $query->orderBy('assessed_at', 'desc')->get();
    }

    /**
     * Calculate pass/fail based on passing criteria.
     *
     * @param TrainingAssessment $assessment
     * @param int $passingPercentage
     * @return bool
     */
    public function isPassed(TrainingAssessment $assessment, int $passingPercentage = 60): bool
    {
        return $assessment->percentage >= $passingPercentage;
    }

    /**
     * Get assessment statistics for a batch.
     *
     * @param int $batchId
     * @return array
     */
    public function getBatchAssessmentStatistics(int $batchId): array
    {
        $assessments = $this->getBatchAssessments($batchId);

        $interimAssessments = $assessments->where('assessment_type', AssessmentType::INTERIM->value);
        $finalAssessments = $assessments->where('assessment_type', AssessmentType::FINAL->value);

        return [
            'total_assessments' => $assessments->count(),
            'interim_count' => $interimAssessments->count(),
            'final_count' => $finalAssessments->count(),
            'interim_average' => $interimAssessments->isNotEmpty()
                ? round($interimAssessments->avg('percentage'), 2)
                : 0,
            'final_average' => $finalAssessments->isNotEmpty()
                ? round($finalAssessments->avg('percentage'), 2)
                : 0,
            'overall_average' => $assessments->isNotEmpty()
                ? round($assessments->avg('percentage'), 2)
                : 0,
            'pass_rate' => $assessments->isNotEmpty()
                ? round(($assessments->where('percentage', '>=', 60)->count() / $assessments->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Bulk create assessments for multiple candidates.
     *
     * @param array $candidateIds
     * @param array $assessmentData
     * @return array
     */
    public function bulkCreateAssessments(array $candidateIds, array $assessmentData): array
    {
        $successful = [];
        $failed = [];

        DB::beginTransaction();
        try {
            foreach ($candidateIds as $candidateId) {
                try {
                    $data = array_merge($assessmentData, ['candidate_id' => $candidateId]);
                    $assessment = $this->createAssessment($data);
                    $successful[] = [
                        'candidate_id' => $candidateId,
                        'assessment_id' => $assessment->id,
                    ];
                } catch (\Exception $e) {
                    $failed[] = [
                        'candidate_id' => $candidateId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            // Log bulk creation
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'assessment_type' => $assessmentData['assessment_type'],
                    'successful_count' => count($successful),
                    'failed_count' => count($failed),
                ])
                ->log('Bulk training assessments created');

            return [
                'successful' => $successful,
                'failed' => $failed,
                'total_processed' => count($candidateIds),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk assessment creation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
