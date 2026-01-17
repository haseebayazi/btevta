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
        'candidate_id',
        'departure_id',
        'campus_id',
        'transaction_reference',
        'transaction_type',
        'transaction_date',
        'amount',
        'currency',
        'exchange_rate',
        'amount_in_pkr',
        'transfer_method',
        'bank_name',
        'account_number',
        'swift_code',
        'iban',
        'purpose',
        'description',
        'month_year',
        'proof_document_path',
        'proof_document_type',
        'proof_document_size',
        'verification_status',
        'verified_by',
        'verified_at',
        'verification_notes',
        'rejection_reason',
        'status',
        'metadata',
        'recorded_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_in_pkr' => 'decimal:2',
        'verified_at' => 'datetime',
        'metadata' => 'array',
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
