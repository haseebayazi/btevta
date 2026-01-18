<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'icon',
        'requires_account_number',
        'requires_bank_name',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'requires_account_number' => 'boolean',
        'requires_bank_name' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Scope to get only active payment methods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->orderBy('display_order');
    }
}
