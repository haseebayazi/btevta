<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RemittanceBeneficiary extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'full_name',
        'relationship',
        'cnic',
        'phone',
        'email',
        'address',
        'city',
        'district',
        'bank_name',
        'account_number',
        'iban',
        'mobile_wallet',
        'is_primary',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * SECURITY: Hide sensitive financial and personal information
     */
    protected $hidden = [
        'cnic',
        'account_number',
        'iban',
    ];

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Get remittances where this beneficiary was the receiver.
     * PHASE 2 FIX: Now uses proper beneficiary_id foreign key.
     *
     * @see database/migrations/2025_12_31_000002_phase2_model_relationship_fixes.php
     */
    public function remittances()
    {
        return $this->hasMany(Remittance::class, 'beneficiary_id');
    }

    /**
     * Get remittances by receiver name (legacy text-based matching).
     * Use remittances() for proper FK relationship instead.
     */
    public function remittancesByName()
    {
        return Remittance::where('candidate_id', $this->candidate_id)
            ->where('receiver_name', $this->full_name);
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Methods
    public function setPrimary()
    {
        // Remove primary status from others
        self::where('candidate_id', $this->candidate_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this as primary
        $this->update(['is_primary' => true]);
    }

    // Accessors
    public function getFullContactAttribute()
    {
        $parts = array_filter([
            $this->phone,
            $this->email,
        ]);
        return implode(' | ', $parts);
    }

    public function getRelationshipLabelAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->relationship));
    }
}
