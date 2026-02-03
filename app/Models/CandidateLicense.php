<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class CandidateLicense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'license_type',
        'license_name',
        'license_number',
        'license_category',
        'issuing_authority',
        'issue_date',
        'expiry_date',
        'file_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the candidate that owns this license
     */
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Check if license is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if license is expiring soon (within 90 days)
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isFuture()
            && now()->diffInDays($this->expiry_date, false) <= 90;
    }

    /**
     * Scope to get expired licenses
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Scope to get expiring soon licenses
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(90));
    }
}
