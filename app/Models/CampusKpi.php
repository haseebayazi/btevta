<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampusKpi extends Model
{
    use HasFactory;

    protected $table = 'campus_kpis';

    protected $fillable = [
        'campus_id',
        'year',
        'month',
        'candidates_registered',
        'candidates_trained',
        'candidates_departed',
        'candidates_rejected',
        'training_completion_rate',
        'assessment_pass_rate',
        'attendance_rate',
        'document_compliance_rate',
        'complaint_resolution_rate',
        'ninety_day_compliance_rate',
        'funding_allocated',
        'funding_utilized',
        'cost_per_candidate',
        'equipment_utilization_rate',
        'equipment_maintenance_count',
        'performance_score',
        'performance_grade',
        'notes',
        'calculated_by',
        'calculated_at',
    ];

    protected $casts = [
        'training_completion_rate' => 'decimal:2',
        'assessment_pass_rate' => 'decimal:2',
        'attendance_rate' => 'decimal:2',
        'document_compliance_rate' => 'decimal:2',
        'complaint_resolution_rate' => 'decimal:2',
        'ninety_day_compliance_rate' => 'decimal:2',
        'funding_allocated' => 'decimal:2',
        'funding_utilized' => 'decimal:2',
        'cost_per_candidate' => 'decimal:2',
        'equipment_utilization_rate' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    // KPI Weights for performance score calculation
    public const KPI_WEIGHTS = [
        'training_completion_rate' => 0.15,
        'assessment_pass_rate' => 0.15,
        'attendance_rate' => 0.10,
        'document_compliance_rate' => 0.10,
        'complaint_resolution_rate' => 0.10,
        'ninety_day_compliance_rate' => 0.10,
        'funding_utilization' => 0.15,
        'equipment_utilization_rate' => 0.15,
    ];

    // Relationships
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function calculatedBy()
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    // Calculate performance score based on weighted KPIs
    public function calculatePerformanceScore(): float
    {
        $fundingUtilization = $this->funding_allocated > 0
            ? ($this->funding_utilized / $this->funding_allocated) * 100
            : 0;

        $score = 0;
        $score += ($this->training_completion_rate ?? 0) * self::KPI_WEIGHTS['training_completion_rate'];
        $score += ($this->assessment_pass_rate ?? 0) * self::KPI_WEIGHTS['assessment_pass_rate'];
        $score += ($this->attendance_rate ?? 0) * self::KPI_WEIGHTS['attendance_rate'];
        $score += ($this->document_compliance_rate ?? 0) * self::KPI_WEIGHTS['document_compliance_rate'];
        $score += ($this->complaint_resolution_rate ?? 0) * self::KPI_WEIGHTS['complaint_resolution_rate'];
        $score += ($this->ninety_day_compliance_rate ?? 0) * self::KPI_WEIGHTS['ninety_day_compliance_rate'];
        $score += min(100, $fundingUtilization) * self::KPI_WEIGHTS['funding_utilization'];
        $score += ($this->equipment_utilization_rate ?? 0) * self::KPI_WEIGHTS['equipment_utilization_rate'];

        return round($score, 2);
    }

    // Determine grade based on score
    public static function getGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }

    // Scopes
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    // Get or create KPI record for a campus/month
    public static function getOrCreateForMonth(int $campusId, int $year, int $month): self
    {
        return static::firstOrCreate(
            ['campus_id' => $campusId, 'year' => $year, 'month' => $month],
            ['calculated_at' => now()]
        );
    }
}
