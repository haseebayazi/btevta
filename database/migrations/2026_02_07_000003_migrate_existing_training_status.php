<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create Training records for candidates already in training
        $candidates = DB::table('candidates')
            ->whereIn('status', ['training', 'visa_process', 'ready', 'departed', 'training_completed'])
            ->orWhere('training_status', 'completed')
            ->get();

        foreach ($candidates as $candidate) {
            $isCompleted = in_array($candidate->training_status, ['completed'])
                || in_array($candidate->status, ['visa_process', 'ready', 'departed', 'training_completed']);
            $isInProgress = $candidate->status === 'training'
                && in_array($candidate->training_status, ['in_progress', 'enrolled']);

            DB::table('trainings')->insertOrIgnore([
                'candidate_id' => $candidate->id,
                'batch_id' => $candidate->batch_id,
                'status' => $isCompleted ? 'completed' : ($isInProgress ? 'in_progress' : 'not_started'),
                'technical_training_status' => $isCompleted ? 'completed' : ($isInProgress ? 'in_progress' : 'not_started'),
                'soft_skills_status' => $isCompleted ? 'completed' : ($isInProgress ? 'in_progress' : 'not_started'),
                'technical_completed_at' => $isCompleted ? ($candidate->training_end_date ?? now()) : null,
                'soft_skills_completed_at' => $isCompleted ? ($candidate->training_end_date ?? now()) : null,
                'completed_at' => $isCompleted ? ($candidate->training_end_date ?? now()) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Set training_type for existing assessments based on assessment_type
        DB::table('training_assessments')
            ->whereNull('training_type')
            ->orWhere('training_type', 'both')
            ->update([
                'training_type' => DB::raw("CASE
                    WHEN assessment_type IN ('initial', 'midterm', 'practical') THEN 'technical'
                    WHEN assessment_type = 'final' THEN 'both'
                    ELSE 'both'
                END"),
            ]);

        // Link existing assessments to training records
        $trainings = DB::table('trainings')->get();
        foreach ($trainings as $training) {
            DB::table('training_assessments')
                ->where('candidate_id', $training->candidate_id)
                ->whereNull('training_id')
                ->update(['training_id' => $training->id]);
        }
    }

    public function down(): void
    {
        // Reset training_type to 'both' for all assessments
        DB::table('training_assessments')->update(['training_type' => 'both', 'training_id' => null]);

        // Remove all training records
        DB::table('trainings')->truncate();
    }
};
