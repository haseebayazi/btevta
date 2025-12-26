<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampusEquipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'campus_equipment';

    protected $fillable = [
        'campus_id',
        'name',
        'equipment_code',
        'category',
        'description',
        'brand',
        'model',
        'serial_number',
        'purchase_date',
        'purchase_cost',
        'current_value',
        'condition',
        'status',
        'quantity',
        'last_maintenance_date',
        'next_maintenance_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'current_value' => 'decimal:2',
    ];

    public const CATEGORIES = [
        'computer' => 'Computer/IT Equipment',
        'machinery' => 'Machinery',
        'furniture' => 'Furniture',
        'tools' => 'Tools & Instruments',
        'vehicle' => 'Vehicle',
        'other' => 'Other',
    ];

    public const CONDITIONS = [
        'excellent' => 'Excellent',
        'good' => 'Good',
        'fair' => 'Fair',
        'poor' => 'Poor',
        'unusable' => 'Unusable',
    ];

    public const STATUSES = [
        'available' => 'Available',
        'in_use' => 'In Use',
        'maintenance' => 'Under Maintenance',
        'retired' => 'Retired',
    ];

    // Relationships
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function usageLogs()
    {
        return $this->hasMany(EquipmentUsageLog::class, 'equipment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeNeedsMaintenance($query)
    {
        return $query->where('next_maintenance_date', '<=', now()->addDays(7))
            ->where('status', '!=', 'retired');
    }

    // Helpers
    public function getUtilizationRate(): float
    {
        $totalHours = $this->usageLogs()
            ->where('usage_type', 'training')
            ->whereMonth('start_time', now()->month)
            ->sum('hours_used');

        // Assuming 8 hours per day, 22 working days per month
        $maxHours = 8 * 22;
        return $maxHours > 0 ? round(($totalHours / $maxHours) * 100, 1) : 0;
    }

    public static function generateEquipmentCode(int $campusId, string $category): string
    {
        $prefix = strtoupper(substr($category, 0, 3));
        $count = static::where('campus_id', $campusId)->count() + 1;
        return sprintf('EQ-%s-%03d-%04d', $prefix, $campusId, $count);
    }
}
