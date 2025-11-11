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

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function remittances()
    {
        return $this->hasMany(Remittance::class, 'receiver_name', 'full_name');
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
