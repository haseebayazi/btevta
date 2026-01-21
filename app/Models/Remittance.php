<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Remittance extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        // Core fields
        'candidate_id',
        'departure_id',
        'campus_id', // v3
        'recorded_by',

        // Transaction identification
        'transaction_reference',
        'transaction_type', // v3
        'transaction_date', // v3
        'transfer_date', // legacy 2025

        // Amount details
        'amount',
        'currency',
        'amount_foreign', // legacy 2025
        'foreign_currency', // legacy 2025
        'exchange_rate',
        'amount_in_pkr', // v3

        // Transfer details
        'transfer_method',
        'bank_name',
        'account_number', // v3
        'swift_code', // v3
        'iban', // v3
        'sender_name', // legacy 2025
        'sender_location', // legacy 2025
        'receiver_name', // legacy 2025
        'receiver_account', // legacy 2025

        // Purpose & description
        'primary_purpose', // legacy 2025 (enum)
        'purpose', // v3 (string)
        'purpose_description', // legacy 2025
        'description', // v3

        // Date tracking
        'month_year', // v3 (YYYY-MM)
        'year', // legacy 2025
        'month', // legacy 2025 (1-12)
        'quarter', // legacy 2025 (1-4)
        'month_number', // legacy 2025

        // Proof documentation
        'has_proof', // legacy 2025 (boolean)
        'proof_document_path', // v3
        'proof_document_type', // v3
        'proof_document_size', // v3
        'proof_verified_date', // legacy 2025

        // Verification workflow
        'verification_status', // v3 (enum)
        'verified_by',
        'verified_at', // v3 (timestamp)
        'verification_notes', // v3
        'rejection_reason', // v3

        // Status & tracking
        'status', // legacy 2025 (pending, verified, flagged, completed)
        'notes', // legacy 2025
        'alert_message', // legacy 2025
        'is_first_remittance', // legacy 2025

        // Flexible data
        'metadata', // v3 (JSON)
    ];

    protected $casts = [
        // Dates
        'transaction_date' => 'date', // v3
        'transfer_date' => 'date', // legacy 2025
        'proof_verified_date' => 'date', // legacy 2025
        'verified_at' => 'datetime', // v3

        // Decimals
        'amount' => 'decimal:2',
        'amount_foreign' => 'decimal:2', // legacy 2025
        'exchange_rate' => 'decimal:4',
        'amount_in_pkr' => 'decimal:2', // v3

        // Integers
        'year' => 'integer', // legacy 2025
        'month' => 'integer', // legacy 2025
        'quarter' => 'integer', // legacy 2025
        'month_number' => 'integer', // legacy 2025
        'proof_document_size' => 'integer', // v3

        // Booleans
        'has_proof' => 'boolean', // legacy 2025
        'is_first_remittance' => 'boolean', // legacy 2025

        // JSON
        'metadata' => 'array', // v3
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['transaction_reference', 'amount', 'currency', 'verification_status', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeVerificationStatus($query, $status)
    {
        return $query->where('verification_status', $status);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForCampus($query, $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopePendingVerification($query)
    {
        return $query->where('verification_status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function hasProof(): bool
    {
        return !empty($this->proof_document_path);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }

    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }

    public function getProofUrlAttribute(): ?string
    {
        if ($this->proof_document_path) {
            return asset('storage/' . $this->proof_document_path);
        }
        return null;
    }
}
