<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RemittanceUsageBreakdown extends Model
{
    use HasFactory;

    protected $table = 'remittance_usage_breakdown';

    protected $fillable = [
        'remittance_id',
        'usage_category',
        'amount',
        'percentage',
        'description',
        'has_proof',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'has_proof' => 'boolean',
    ];

    // Relationships
    public function remittance()
    {
        return $this->belongsTo(Remittance::class);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    public function getCategoryLabelAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->usage_category));
    }

    // Methods
    public function calculatePercentage()
    {
        if ($this->remittance && $this->remittance->amount > 0) {
            $this->percentage = ($this->amount / $this->remittance->amount) * 100;
            $this->save();
        }
    }
}
