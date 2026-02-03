<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Program;
use App\Models\ImplementingPartner;
use App\Models\Trade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AllocationService
{
    /**
     * Allocate campus, program, implementing partner, and trade to a candidate.
     *
     * @param Candidate $candidate
     * @param array $allocationData
     * @return Candidate
     * @throws \Exception
     */
    public function allocate(Candidate $candidate, array $allocationData): Candidate
    {
        // Validate allocation data
        $this->validateAllocationData($allocationData);

        // Use DB::transaction() closure to properly support nested transactions/savepoints
        DB::transaction(function () use ($candidate, $allocationData) {
            // Update candidate allocation fields
            $candidate->campus_id = $allocationData['campus_id'];
            $candidate->program_id = $allocationData['program_id'];
            $candidate->implementing_partner_id = $allocationData['implementing_partner_id'] ?? null;
            $candidate->trade_id = $allocationData['trade_id'];
            $candidate->oep_id = $allocationData['oep_id'] ?? null;

            $candidate->save();
        });

        // Log the allocation (outside transaction as it's not critical)
        activity()
            ->performedOn($candidate)
            ->causedBy(auth()->user())
            ->withProperties($allocationData)
            ->log('Candidate allocation updated');

        return $candidate;
    }

    /**
     * Validate allocation data.
     *
     * @param array $data
     * @return void
     * @throws \Exception
     */
    protected function validateAllocationData(array $data): void
    {
        // Validate required fields
        if (!isset($data['campus_id']) || !isset($data['program_id']) || !isset($data['trade_id'])) {
            throw new \Exception('Campus, program, and trade are required for allocation.');
        }

        // Validate campus exists
        if (!Campus::find($data['campus_id'])) {
            throw new \Exception('Invalid campus ID provided.');
        }

        // Validate program exists and is active
        $program = Program::find($data['program_id']);
        if (!$program) {
            throw new \Exception('Invalid program ID provided.');
        }
        if (!$program->is_active) {
            throw new \Exception('Selected program is not active.');
        }

        // Validate trade exists
        if (!Trade::find($data['trade_id'])) {
            throw new \Exception('Invalid trade ID provided.');
        }

        // Validate implementing partner if provided
        if (isset($data['implementing_partner_id']) && $data['implementing_partner_id']) {
            $partner = ImplementingPartner::find($data['implementing_partner_id']);
            if (!$partner) {
                throw new \Exception('Invalid implementing partner ID provided.');
            }
            if (!$partner->is_active) {
                throw new \Exception('Selected implementing partner is not active.');
            }
        }
    }

    /**
     * Get allocation summary for a candidate.
     *
     * @param Candidate $candidate
     * @return array
     */
    public function getAllocationSummary(Candidate $candidate): array
    {
        $candidate->load(['campus', 'program', 'implementingPartner', 'trade', 'oep']);

        return [
            'campus' => $candidate->campus?->name,
            'program' => $candidate->program?->name,
            'implementing_partner' => $candidate->implementingPartner?->name,
            'trade' => $candidate->trade?->name,
            'oep' => $candidate->oep?->name,
        ];
    }

    /**
     * Check if candidate is fully allocated.
     *
     * @param Candidate $candidate
     * @return bool
     */
    public function isFullyAllocated(Candidate $candidate): bool
    {
        return $candidate->campus_id
            && $candidate->program_id
            && $candidate->trade_id;
    }

    /**
     * Get allocation statistics for a campus/program combination.
     *
     * @param int $campusId
     * @param int $programId
     * @return array
     */
    public function getAllocationStatistics(int $campusId, int $programId): array
    {
        $totalAllocated = Candidate::where('campus_id', $campusId)
            ->where('program_id', $programId)
            ->count();

        $byTrade = Candidate::where('campus_id', $campusId)
            ->where('program_id', $programId)
            ->with('trade')
            ->get()
            ->groupBy('trade_id')
            ->map(function ($candidates, $tradeId) {
                $trade = $candidates->first()->trade;
                return [
                    'trade_id' => $tradeId,
                    'trade_name' => $trade->name ?? 'Unknown',
                    'count' => $candidates->count(),
                ];
            })
            ->values();

        $byPartner = Candidate::where('campus_id', $campusId)
            ->where('program_id', $programId)
            ->whereNotNull('implementing_partner_id')
            ->with('implementingPartner')
            ->get()
            ->groupBy('implementing_partner_id')
            ->map(function ($candidates, $partnerId) {
                $partner = $candidates->first()->implementingPartner;
                return [
                    'partner_id' => $partnerId,
                    'partner_name' => $partner->name ?? 'Unknown',
                    'count' => $candidates->count(),
                ];
            })
            ->values();

        return [
            'total_allocated' => $totalAllocated,
            'by_trade' => $byTrade,
            'by_partner' => $byPartner,
        ];
    }

    /**
     * Bulk allocate candidates to a campus/program/partner/trade.
     *
     * @param array $candidateIds
     * @param array $allocationData
     * @return array
     */
    public function bulkAllocate(array $candidateIds, array $allocationData): array
    {
        $this->validateAllocationData($allocationData);

        // Use DB::transaction() closure to properly support nested transactions/savepoints
        $result = DB::transaction(function () use ($candidateIds, $allocationData) {
            $successful = [];
            $failed = [];

            foreach ($candidateIds as $candidateId) {
                try {
                    $candidate = Candidate::findOrFail($candidateId);
                    $this->allocate($candidate, $allocationData);
                    $successful[] = $candidateId;
                } catch (\Exception $e) {
                    $failed[$candidateId] = $e->getMessage();
                }
            }

            return [
                'success' => $successful,
                'failed' => $failed,
                'total_processed' => count($candidateIds),
            ];
        });

        // Log bulk allocation (outside transaction as it's not critical)
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'allocation_data' => $allocationData,
                'successful_count' => count($result['success']),
                'failed_count' => count($result['failed']),
            ])
            ->log('Bulk candidate allocation');

        return $result;
    }

    /**
     * Update allocation for a candidate.
     *
     * @param Candidate $candidate
     * @param array $allocationData
     * @return Candidate
     * @throws \Exception
     */
    public function updateAllocation(Candidate $candidate, array $allocationData): Candidate
    {
        // Store old allocation for logging
        $oldAllocation = [
            'campus_id' => $candidate->campus_id,
            'program_id' => $candidate->program_id,
            'implementing_partner_id' => $candidate->implementing_partner_id,
            'trade_id' => $candidate->trade_id,
        ];

        // Perform allocation
        $candidate = $this->allocate($candidate, $allocationData);

        // Log the change
        activity()
            ->performedOn($candidate)
            ->causedBy(auth()->user())
            ->withProperties([
                'old' => $oldAllocation,
                'new' => $allocationData,
            ])
            ->log('Candidate allocation changed');

        return $candidate;
    }

    /**
     * Clear allocation for a candidate.
     *
     * @param Candidate $candidate
     * @return Candidate
     */
    public function clearAllocation(Candidate $candidate): Candidate
    {
        // Use DB::transaction() closure to properly support nested transactions/savepoints
        DB::transaction(function () use ($candidate) {
            $candidate->campus_id = null;
            $candidate->program_id = null;
            $candidate->implementing_partner_id = null;
            $candidate->trade_id = null;
            $candidate->batch_id = null;
            $candidate->allocated_number = null;

            $candidate->save();
        });

        // Log the clearing (outside transaction as it's not critical)
        activity()
            ->performedOn($candidate)
            ->causedBy(auth()->user())
            ->log('Candidate allocation cleared');

        return $candidate;
    }

    /**
     * Get available programs for a campus.
     *
     * @param int $campusId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailablePrograms(int $campusId)
    {
        return Program::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get available implementing partners.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableImplementingPartners()
    {
        return ImplementingPartner::where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
