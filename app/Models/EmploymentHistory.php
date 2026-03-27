<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmploymentHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employment_histories';

    protected $fillable = [
        'candidate_id',
        'post_departure_detail_id',
        'departure_id',
        'employer_id',
        'company_name',
        'company_address',
        'employer_contact_name',
        'employer_contact_phone',
        'employer_contact_email',
        'position_title',
        'department',
        'work_location',
        'base_salary',
        'salary_currency',
        'housing_allowance',
        'food_allowance',
        'transport_allowance',
        'other_allowance',
        'benefits',
        'commencement_date',
        'end_date',
        'terms_conditions',
        'contract_path',
        'status',
        'sequence',
        'termination_reason',
        // Legacy fields
        'switch_number',
        'salary',
        'job_terms',
        'special_conditions',
        'switch_date',
        'reason_for_switch',
        'recorded_by',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'housing_allowance' => 'decimal:2',
        'food_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'other_allowance' => 'decimal:2',
        'salary' => 'decimal:2',
        'benefits' => 'array',
        'commencement_date' => 'date',
        'end_date' => 'date',
        'switch_date' => 'date',
        'switch_number' => 'integer',
        'status' => EmploymentStatus::class,
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function postDepartureDetail()
    {
        return $this->belongsTo(PostDepartureDetail::class);
    }

    public function departure()
    {
        return $this->belongsTo(Departure::class);
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // -----------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------

    public function getTotalPackageAttribute(): float
    {
        return ($this->base_salary ?? 0)
            + ($this->housing_allowance ?? 0)
            + ($this->food_allowance ?? 0)
            + ($this->transport_allowance ?? 0)
            + ($this->other_allowance ?? 0);
    }

    public function getEmploymentDurationAttribute(): ?string
    {
        if (!$this->commencement_date) return null;

        $endDate = $this->end_date ?? now();
        $diff = $this->commencement_date->diff($endDate);

        if ($diff->y > 0) {
            return $diff->y . ' year(s), ' . $diff->m . ' month(s)';
        }
        return $diff->m . ' month(s), ' . $diff->d . ' day(s)';
    }

    public function getSequenceLabelAttribute(): string
    {
        return match($this->sequence) {
            1 => 'Initial Employment',
            2 => 'First Company Switch',
            3 => 'Second Company Switch',
            default => "Employment #{$this->sequence}",
        };
    }

    // -----------------------------------------------------------------------
    // Methods
    // -----------------------------------------------------------------------

    public function terminate(string $reason, ?string $endDate = null): void
    {
        $this->status = EmploymentStatus::TERMINATED;
        $this->termination_reason = $reason;
        $this->end_date = $endDate ?? now();
        $this->save();

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['reason' => $reason])
            ->log('Employment terminated');
    }

    public function markAsPrevious(): void
    {
        $this->status = EmploymentStatus::PREVIOUS;
        $this->end_date = $this->end_date ?? now();
        $this->save();
    }
}
