<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Program;
use App\Models\Trade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoBatchService
{
    /**
     * Get or create a batch for a candidate based on campus, program, and trade.
     *
     * @param Candidate $candidate
     * @return Batch
     * @throws \Exception
     */
    public function assignOrCreateBatch(Candidate $candidate): Batch
    {
        // Validate required fields
        if (!$candidate->campus_id || !$candidate->program_id || !$candidate->trade_id) {
            throw new \Exception('Candidate must have campus, program, and trade assigned.');
        }

        DB::beginTransaction();
        try {
            // Find an existing batch that matches the criteria and has available slots
            $batch = $this->findAvailableBatch(
                $candidate->campus_id,
                $candidate->program_id,
                $candidate->trade_id
            );

            // Create a new batch if no available batch exists
            if (!$batch) {
                $batch = $this->createNewBatch(
                    $candidate->campus_id,
                    $candidate->program_id,
                    $candidate->trade_id,
                    $candidate->oep_id
                );
            }

            // Assign candidate to batch
            $candidate->batch_id = $batch->id;

            // Generate and assign allocated number
            $candidate->allocated_number = $this->generateAllocatedNumber($candidate, $batch);
            $candidate->save();

            DB::commit();

            // Log the assignment
            activity()
                ->performedOn($batch)
                ->causedBy(auth()->user())
                ->withProperties([
                    'candidate_id' => $candidate->id,
                    'allocated_number' => $candidate->allocated_number,
                ])
                ->log('Candidate assigned to batch');

            return $batch;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign candidate to batch', [
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Find an available batch matching the criteria.
     *
     * @param int $campusId
     * @param int $programId
     * @param int $tradeId
     * @return Batch|null
     */
    protected function findAvailableBatch(int $campusId, int $programId, int $tradeId): ?Batch
    {
        $batchSize = $this->getBatchSize();

        return Batch::where('campus_id', $campusId)
            ->where('trade_id', $tradeId)
            ->where('status', Batch::STATUS_PLANNED)
            ->whereHas('candidates', function ($query) use ($batchSize) {
                // Only get batches in current year for auto-assignment
                $query->whereYear('created_at', now()->year);
            }, '<', $batchSize)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Create a new batch with auto-generated batch number.
     *
     * @param int $campusId
     * @param int $programId
     * @param int $tradeId
     * @param int|null $oepId
     * @return Batch
     * @throws \Exception
     */
    protected function createNewBatch(int $campusId, int $programId, int $tradeId, ?int $oepId = null): Batch
    {
        $campus = Campus::findOrFail($campusId);
        $program = Program::findOrFail($programId);
        $trade = Trade::findOrFail($tradeId);

        $batchNumber = $this->generateBatchNumber($campus, $program, $trade);
        $batchSize = $this->getBatchSize();

        $batch = Batch::create([
            'batch_code' => $batchNumber,
            'name' => $this->generateBatchName($campus, $program, $trade),
            'campus_id' => $campusId,
            'trade_id' => $tradeId,
            'oep_id' => $oepId,
            'capacity' => $batchSize,
            'status' => Batch::STATUS_PLANNED,
            'intake_period' => now()->format('Y-m'),
            'created_by' => auth()->id(),
        ]);

        // Log batch creation
        activity()
            ->performedOn($batch)
            ->causedBy(auth()->user())
            ->log('Auto-created new batch');

        return $batch;
    }

    /**
     * Generate a unique batch number.
     * Format: CAMPUS-PROGRAM-TRADE-YEAR-SEQUENCE
     * Example: LHR-KSAWP-ELEC-2026-0001
     *
     * @param Campus $campus
     * @param Program $program
     * @param Trade $trade
     * @return string
     */
    public function generateBatchNumber(Campus $campus, Program $program, Trade $trade): string
    {
        $year = now()->format('Y');

        // Get the last batch with the same combination in the current year
        $lastBatch = Batch::where('campus_id', $campus->id)
            ->where('trade_id', $trade->id)
            ->where('batch_code', 'like', sprintf(
                '%s-%s-%s-%s-%%',
                $campus->code,
                $program->code,
                $trade->code,
                $year
            ))
            ->orderBy('id', 'desc')
            ->first();

        // Extract sequence from last batch or start from 1
        $sequence = $lastBatch
            ? (int) substr($lastBatch->batch_code, -4) + 1
            : 1;

        // Format: CAMPUS-PROGRAM-TRADE-YEAR-SEQUENCE
        return sprintf(
            '%s-%s-%s-%s-%04d',
            strtoupper($campus->code),
            strtoupper($program->code),
            strtoupper($trade->code),
            $year,
            $sequence
        );
    }

    /**
     * Generate allocated number for a candidate within a batch.
     * Format: BATCH_NUMBER-POSITION
     * Example: LHR-KSAWP-ELEC-2026-0001-025
     *
     * @param Candidate $candidate
     * @param Batch $batch
     * @return string
     */
    public function generateAllocatedNumber(Candidate $candidate, Batch $batch): string
    {
        // Get the current position in the batch (excluding the current candidate if already assigned)
        $position = $batch->candidates()
            ->where('id', '!=', $candidate->id)
            ->count() + 1;

        // Format: BATCH_NUMBER-POSITION (3 digits)
        return sprintf(
            '%s-%03d',
            $batch->batch_code,
            $position
        );
    }

    /**
     * Generate a human-readable batch name.
     *
     * @param Campus $campus
     * @param Program $program
     * @param Trade $trade
     * @return string
     */
    protected function generateBatchName(Campus $campus, Program $program, Trade $trade): string
    {
        $month = now()->format('F Y');
        return sprintf(
            '%s - %s - %s (%s)',
            $campus->name,
            $trade->name,
            $program->name,
            $month
        );
    }

    /**
     * Get configured batch size from settings.
     * Default: 25, Allowed: 20, 25, 30
     *
     * @return int
     */
    protected function getBatchSize(): int
    {
        // Try to get from settings table or config
        $size = config('wasl.batch_size', 25);

        // Validate that it's one of the allowed sizes
        $allowedSizes = [20, 25, 30];
        if (!in_array($size, $allowedSizes)) {
            Log::warning('Invalid batch size configured, using default', [
                'configured' => $size,
                'default' => 25,
            ]);
            return 25;
        }

        return $size;
    }

    /**
     * Reassign allocated numbers for all candidates in a batch.
     * Useful after candidate removals or reordering.
     *
     * @param Batch $batch
     * @return void
     */
    public function reassignAllocatedNumbers(Batch $batch): void
    {
        DB::beginTransaction();
        try {
            $candidates = $batch->candidates()->orderBy('created_at')->get();

            foreach ($candidates as $index => $candidate) {
                $position = $index + 1;
                $candidate->allocated_number = sprintf(
                    '%s-%03d',
                    $batch->batch_code,
                    $position
                );
                $candidate->save();
            }

            DB::commit();

            // Log the reassignment
            activity()
                ->performedOn($batch)
                ->causedBy(auth()->user())
                ->withProperties(['candidate_count' => $candidates->count()])
                ->log('Batch allocated numbers reassigned');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reassign allocated numbers', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if a batch can accept more candidates.
     *
     * @param Batch $batch
     * @return bool
     */
    public function canAcceptCandidates(Batch $batch): bool
    {
        $batchSize = $this->getBatchSize();
        $currentCount = $batch->candidates()->count();

        return $currentCount < $batchSize;
    }

    /**
     * Get batch statistics.
     *
     * @param Batch $batch
     * @return array
     */
    public function getBatchStatistics(Batch $batch): array
    {
        $batchSize = $this->getBatchSize();
        $currentCount = $batch->candidates()->count();

        return [
            'batch_number' => $batch->batch_code,
            'capacity' => $batchSize,
            'current_count' => $currentCount,
            'available_slots' => max(0, $batchSize - $currentCount),
            'is_full' => $currentCount >= $batchSize,
            'fill_percentage' => $batchSize > 0 ? round(($currentCount / $batchSize) * 100, 2) : 0,
        ];
    }
}
