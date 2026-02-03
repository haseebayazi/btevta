<?php

namespace App\Services;

use App\Models\Remittance;
use App\Models\Candidate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class RemittanceService
{
    /**
     * Create a new remittance record
     */
    public function createRemittance(array $data, ?UploadedFile $proofFile = null): Remittance
    {
        // Generate unique transaction reference if not provided
        if (empty($data['transaction_reference'])) {
            $data['transaction_reference'] = $this->generateTransactionReference();
        }

        // Calculate PKR amount if exchange rate provided
        if (!empty($data['exchange_rate']) && !empty($data['amount'])) {
            $data['amount_in_pkr'] = $data['amount'] * $data['exchange_rate'];
        }

        // Handle proof document upload
        if ($proofFile) {
            $proofPath = $proofFile->store('remittances/proofs', 'public');
            $data['proof_document_path'] = $proofPath;
            $data['proof_document_type'] = $proofFile->getClientOriginalExtension();
            $data['proof_document_size'] = $proofFile->getSize();
        }

        // Use DB::transaction() closure to properly support nested transactions/savepoints
        return DB::transaction(function () use ($data) {
            return Remittance::create($data);
        });
    }

    /**
     * Update remittance record
     */
    public function updateRemittance(Remittance $remittance, array $data, ?UploadedFile $proofFile = null): Remittance
    {
        // Recalculate PKR amount if exchange rate or amount changed
        if (isset($data['exchange_rate']) || isset($data['amount'])) {
            $amount = $data['amount'] ?? $remittance->amount;
            $exchangeRate = $data['exchange_rate'] ?? $remittance->exchange_rate;
            
            if ($amount && $exchangeRate) {
                $data['amount_in_pkr'] = $amount * $exchangeRate;
            }
        }

        // Handle new proof document upload
        if ($proofFile) {
            // Delete old proof if exists
            if ($remittance->proof_document_path) {
                Storage::disk('public')->delete($remittance->proof_document_path);
            }

            $proofPath = $proofFile->store('remittances/proofs', 'public');
            $data['proof_document_path'] = $proofPath;
            $data['proof_document_type'] = $proofFile->getClientOriginalExtension();
            $data['proof_document_size'] = $proofFile->getSize();
        }

        // Use DB::transaction() closure to properly support nested transactions/savepoints
        return DB::transaction(function () use ($remittance, $data) {
            $remittance->update($data);
            return $remittance->fresh();
        });
    }

    /**
     * Verify a remittance
     */
    public function verifyRemittance(Remittance $remittance, int $verifiedBy, ?string $notes = null): Remittance
    {
        $remittance->update([
            'verification_status' => 'verified',
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
            'verification_notes' => $notes,
            'status' => 'completed',
        ]);

        return $remittance->fresh();
    }

    /**
     * Reject a remittance verification
     */
    public function rejectRemittance(Remittance $remittance, int $verifiedBy, string $reason): Remittance
    {
        $remittance->update([
            'verification_status' => 'rejected',
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
            'rejection_reason' => $reason,
            'status' => 'failed',
        ]);

        return $remittance->fresh();
    }

    /**
     * Mark remittance as under review
     */
    public function markUnderReview(Remittance $remittance, ?string $notes = null): Remittance
    {
        $remittance->update([
            'verification_status' => 'under_review',
            'verification_notes' => $notes,
        ]);

        return $remittance->fresh();
    }

    /**
     * Get remittance statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $query = Remittance::query();

        // Apply filters
        if (!empty($filters['campus_id'])) {
            $query->where('campus_id', $filters['campus_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        }

        return [
            'total_remittances' => (clone $query)->count(),
            'total_amount' => (clone $query)->sum('amount'),
            'total_amount_pkr' => (clone $query)->sum('amount_in_pkr'),
            'pending_verification' => (clone $query)->where('verification_status', 'pending')->count(),
            'verified' => (clone $query)->where('verification_status', 'verified')->count(),
            'rejected' => (clone $query)->where('verification_status', 'rejected')->count(),
            'under_review' => (clone $query)->where('verification_status', 'under_review')->count(),
            'by_currency' => (clone $query)->select('currency', DB::raw('SUM(amount) as total'))
                ->groupBy('currency')
                ->get()
                ->pluck('total', 'currency')
                ->toArray(),
            'by_transaction_type' => (clone $query)->select('transaction_type', DB::raw('COUNT(*) as count'))
                ->groupBy('transaction_type')
                ->get()
                ->pluck('count', 'transaction_type')
                ->toArray(),
            'by_month' => (clone $query)->select('month_year', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                ->whereNotNull('month_year')
                ->groupBy('month_year')
                ->orderBy('month_year', 'desc')
                ->limit(12)
                ->get(),
        ];
    }

    /**
     * Get candidate remittance history
     */
    public function getCandidateRemittances(int $candidateId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Remittance::where('candidate_id', $candidateId)
            ->with(['campus', 'departure', 'verifiedBy', 'recordedBy'])
            ->orderBy('transaction_date', 'desc');

        if (!empty($filters['verification_status'])) {
            $query->where('verification_status', $filters['verification_status']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    /**
     * Generate unique transaction reference
     */
    protected function generateTransactionReference(): string
    {
        $prefix = 'RMT';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        
        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Delete proof document
     */
    public function deleteProof(Remittance $remittance): bool
    {
        if ($remittance->proof_document_path) {
            Storage::disk('public')->delete($remittance->proof_document_path);
            
            $remittance->update([
                'proof_document_path' => null,
                'proof_document_type' => null,
                'proof_document_size' => null,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get remittances requiring verification
     */
    public function getPendingVerifications(int $campusId = null)
    {
        $query = Remittance::with(['candidate', 'campus', 'recordedBy'])
            ->where('verification_status', 'pending')
            ->orderBy('created_at', 'asc');

        if ($campusId) {
            $query->where('campus_id', $campusId);
        }

        return $query->paginate(20);
    }
}
