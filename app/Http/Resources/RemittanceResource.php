<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemittanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidate_id' => $this->candidate_id,
            'departure_id' => $this->departure_id,
            'transaction_reference' => $this->transaction_reference,

            // Amount Information
            'amount' => [
                'value' => (float) $this->amount,
                'currency' => $this->currency ?? 'PKR',
                'formatted' => $this->getFormattedAmount(),
            ],

            'foreign_amount' => $this->when($this->amount_foreign, [
                'value' => (float) $this->amount_foreign,
                'currency' => $this->foreign_currency,
                'exchange_rate' => $this->exchange_rate,
            ]),

            // Transfer Details
            'transfer' => [
                'date' => $this->transfer_date?->format('Y-m-d'),
                'method' => $this->transfer_method,
                'method_label' => $this->getTransferMethodLabel(),
            ],

            // Sender Information
            'sender' => [
                'name' => $this->sender_name,
                'location' => $this->sender_location,
            ],

            // Receiver Information
            'receiver' => [
                'name' => $this->receiver_name,
                'account' => $this->when($this->canViewSensitive($request), $this->receiver_account),
                'bank_name' => $this->bank_name,
            ],

            // Purpose
            'purpose' => [
                'primary' => $this->primary_purpose,
                'primary_label' => $this->getPurposeLabel(),
                'description' => $this->purpose_description,
            ],

            // Usage Breakdown
            'usage_breakdown' => $this->whenLoaded('usageBreakdown', fn() =>
                $this->usageBreakdown->map(fn($usage) => [
                    'purpose' => $usage->purpose,
                    'amount' => (float) $usage->amount,
                    'percentage' => $usage->percentage,
                    'description' => $usage->description,
                ])
            ),

            // Proof & Verification
            'proof' => [
                'has_proof' => (bool) $this->has_proof,
                'verified' => (bool) $this->proof_verified_date,
                'verified_date' => $this->proof_verified_date?->format('Y-m-d'),
            ],

            'receipts' => $this->whenLoaded('receipts', fn() =>
                $this->receipts->map(fn($receipt) => [
                    'id' => $receipt->id,
                    'file_name' => $receipt->file_name,
                    'file_type' => $receipt->file_type,
                    'file_size' => $receipt->file_size,
                    'is_verified' => (bool) $receipt->is_verified,
                    'uploaded_at' => $receipt->created_at?->toIso8601String(),
                ])
            ),

            // Status
            'status' => [
                'value' => $this->status,
                'label' => $this->getStatusLabel(),
                'color' => $this->getStatusColor(),
            ],

            // Metadata
            'is_first_remittance' => (bool) $this->is_first_remittance,
            'month_number' => $this->month_number,
            'notes' => $this->notes,
            'alert_message' => $this->alert_message,

            // Time Period
            'period' => [
                'year' => $this->year,
                'month' => $this->month,
                'quarter' => $this->quarter,
            ],

            // Relationships
            'candidate' => $this->whenLoaded('candidate', fn() => [
                'id' => $this->candidate->id,
                'name' => $this->candidate->name,
                'btevta_id' => $this->candidate->btevta_id,
            ]),

            'departure' => $this->whenLoaded('departure', fn() => [
                'id' => $this->departure->id,
                'departure_date' => $this->departure->departure_date?->format('Y-m-d'),
                'destination' => $this->departure->destination,
            ]),

            'recorded_by' => $this->whenLoaded('recordedBy', fn() => [
                'id' => $this->recordedBy->id,
                'name' => $this->recordedBy->name,
            ]),

            'verified_by' => $this->whenLoaded('verifiedBy', fn() => [
                'id' => $this->verifiedBy->id,
                'name' => $this->verifiedBy->name,
            ]),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get formatted amount with currency
     */
    protected function getFormattedAmount(): string
    {
        $currency = $this->currency ?? 'PKR';
        return $currency . ' ' . number_format($this->amount, 2);
    }

    /**
     * Get transfer method label
     */
    protected function getTransferMethodLabel(): string
    {
        $methods = [
            'bank_transfer' => 'Bank Transfer',
            'money_exchange' => 'Money Exchange',
            'online_transfer' => 'Online Transfer',
            'cash_deposit' => 'Cash Deposit',
            'mobile_wallet' => 'Mobile Wallet',
            'other' => 'Other',
        ];

        return $methods[$this->transfer_method] ?? $this->transfer_method ?? 'Unknown';
    }

    /**
     * Get purpose label
     */
    protected function getPurposeLabel(): string
    {
        $purposes = [
            'family_support' => 'Family Support',
            'education' => 'Education',
            'healthcare' => 'Healthcare',
            'debt_repayment' => 'Debt Repayment',
            'savings' => 'Savings',
            'investment' => 'Investment',
            'property' => 'Property/Real Estate',
            'business' => 'Business',
            'other' => 'Other',
        ];

        return $purposes[$this->primary_purpose] ?? $this->primary_purpose ?? 'Unknown';
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(): string
    {
        $statuses = [
            'pending' => 'Pending Verification',
            'verified' => 'Verified',
            'flagged' => 'Flagged for Review',
        ];

        return $statuses[$this->status] ?? $this->status ?? 'Unknown';
    }

    /**
     * Get status color
     */
    protected function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'verified' => 'success',
            'flagged' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Check if the authenticated user can view sensitive information
     */
    protected function canViewSensitive(Request $request): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole(['admin', 'campus_admin', 'project_director', 'oep']);
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'purposes' => config('remittance.purposes', []),
                'transfer_methods' => config('remittance.transfer_methods', []),
                'statuses' => config('remittance.statuses', []),
            ],
        ];
    }
}
