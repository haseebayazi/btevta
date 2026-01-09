<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Remittance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'departure_id',
        'beneficiary_id',
        'recorded_by',
        'transaction_reference',
        'amount',
        'currency',
        'amount_foreign',
        'foreign_currency',
        'exchange_rate',
        'transfer_date',
        'transfer_method',
        'sender_name',
        'sender_location',
        'receiver_name',
        'receiver_account',
        'bank_name',
        'primary_purpose',
        'purpose_description',
        'has_proof',
        'proof_verified_date',
        'verified_by',
        'status',
        'notes',
        'alert_message',
        'is_first_remittance',
        'month_number',
        'year',
        'month',
        'quarter',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'proof_verified_date' => 'date',
        'amount' => 'decimal:2',
        'amount_foreign' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'has_proof' => 'boolean',
        'is_first_remittance' => 'boolean',
        'year' => 'integer',
        'month' => 'integer',
        'quarter' => 'integer',
    ];

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function departure()
    {
        return $this->belongsTo(Departure::class);
    }

    /**
     * AUDIT FIX: Added missing beneficiary relationship
     * The beneficiary_id column was added in migration phase2_model_relationship_fixes
     */
    public function beneficiary()
    {
        return $this->belongsTo(RemittanceBeneficiary::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function receipts()
    {
        return $this->hasMany(RemittanceReceipt::class);
    }

    public function usageBreakdown()
    {
        return $this->hasMany(RemittanceUsageBreakdown::class);
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByMonth($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeByPurpose($query, $purpose)
    {
        return $query->where('primary_purpose', $purpose);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getMonthNameAttribute()
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }

    // Mutators
    public function setTransferDateAttribute($value)
    {
        $this->attributes['transfer_date'] = $value;

        // Auto-set year, month, quarter
        if ($value) {
            $date = \Carbon\Carbon::parse($value);
            $this->attributes['year'] = $date->year;
            $this->attributes['month'] = $date->month;
            $this->attributes['quarter'] = $date->quarter;
        }
    }

    // Methods
    public function calculateMonthNumber()
    {
        if ($this->departure && $this->departure->departure_date) {
            $deploymentDate = \Carbon\Carbon::parse($this->departure->departure_date);
            $transferDate = \Carbon\Carbon::parse($this->transfer_date);
            return $deploymentDate->diffInMonths($transferDate) + 1;
        }
        return null;
    }

    public function markAsVerified($userId)
    {
        $this->update([
            'status' => 'verified',
            'verified_by' => $userId,
            'proof_verified_date' => now(),
        ]);
    }

    public function hasCompleteProof()
    {
        return $this->has_proof && $this->receipts()->where('is_verified', true)->exists();
    }
}
