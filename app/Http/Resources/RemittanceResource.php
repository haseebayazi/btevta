<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemittanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_reference' => $this->transaction_reference,
            'transaction_type' => $this->transaction_type,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            
            // Amount details
            'amount' => $this->amount,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'amount_in_pkr' => $this->amount_in_pkr,
            'formatted_amount' => $this->formatted_amount,
            
            // Transfer details
            'transfer_method' => $this->transfer_method,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'swift_code' => $this->swift_code,
            'iban' => $this->iban,
            
            // Purpose
            'purpose' => $this->purpose,
            'description' => $this->description,
            'month_year' => $this->month_year,
            
            // Documentation
            'has_proof' => $this->hasProof(),
            'proof_url' => $this->proof_url,
            'proof_document_type' => $this->proof_document_type,
            'proof_document_size' => $this->proof_document_size,
            
            // Verification
            'verification_status' => $this->verification_status,
            'is_verified' => $this->isVerified(),
            'is_pending' => $this->isPending(),
            'verified_at' => $this->verified_at?->format('Y-m-d H:i:s'),
            'verification_notes' => $this->verification_notes,
            'rejection_reason' => $this->rejection_reason,
            
            // Status
            'status' => $this->status,
            
            // Relationships
            'candidate' => $this->when($this->relationLoaded('candidate'), [
                'id' => $this->candidate?->id,
                'name' => $this->candidate?->name,
                'passport_number' => $this->candidate?->passport_number,
            ]),
            
            'campus' => $this->when($this->relationLoaded('campus'), [
                'id' => $this->campus?->id,
                'name' => $this->campus?->name,
            ]),
            
            'departure' => $this->when($this->relationLoaded('departure'), [
                'id' => $this->departure?->id,
                'departure_date' => $this->departure?->departure_date?->format('Y-m-d'),
            ]),
            
            'verified_by' => $this->when($this->relationLoaded('verifiedBy'), [
                'id' => $this->verifiedBy?->id,
                'name' => $this->verifiedBy?->name,
            ]),
            
            'recorded_by' => $this->when($this->relationLoaded('recordedBy'), [
                'id' => $this->recordedBy?->id,
                'name' => $this->recordedBy?->name,
            ]),
            
            // Metadata
            'metadata' => $this->metadata,
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
