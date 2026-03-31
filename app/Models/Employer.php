<?php

namespace App\Models;

use App\Enums\EmployerSize;
use App\ValueObjects\EmploymentPackage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'permission_number',
        'permission_issue_date',
        'permission_expiry_date',
        'permission_document_path',
        'visa_issuing_company',
        'visa_company_license',
        'country_id',
        'city',
        'sector',
        'trade',
        'trade_id',
        'basic_salary',
        'salary_currency',
        'food_by_company',
        'transport_by_company',
        'accommodation_by_company',
        'other_conditions',
        'default_package',
        'company_size',
        'verified',
        'verified_at',
        'verified_by',
        'notes',
        'evidence_path',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'basic_salary' => 'float',
        'food_by_company' => 'boolean',
        'transport_by_company' => 'boolean',
        'accommodation_by_company' => 'boolean',
        'is_active' => 'boolean',
        'permission_issue_date' => 'date',
        'permission_expiry_date' => 'date',
        'default_package' => 'array',
        'company_size' => EmployerSize::class,
        'verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the country for this employer
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the trade relationship for this employer
     */
    public function tradeRelation()
    {
        return $this->belongsTo(Trade::class, 'trade_id');
    }

    /**
     * Get the user who created this employer
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who verified this employer
     */
    public function verifiedByUser()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get employer documents
     */
    public function documents()
    {
        return $this->hasMany(EmployerDocument::class);
    }

    /**
     * Get candidates associated with this employer
     */
    public function candidates()
    {
        return $this->belongsToMany(Candidate::class, 'candidate_employer')
            ->withPivot(['is_current', 'assigned_at', 'assigned_by', 'employment_type', 'assignment_date', 'custom_package', 'status'])
            ->withTimestamps();
    }

    /**
     * Get current candidates for this employer
     */
    public function currentCandidates()
    {
        return $this->candidates()->wherePivot('is_current', true);
    }

    /**
     * Get active candidates (by pivot status)
     */
    public function activeCandidates()
    {
        return $this->candidates()->wherePivot('status', 'active');
    }

    /**
     * Scope to get only active employers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get verified employers
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope to get employers with expiring permissions
     */
    public function scopeExpiringPermissions($query, int $days = 30)
    {
        return $query->whereNotNull('permission_expiry_date')
            ->where('permission_expiry_date', '>', now())
            ->where('permission_expiry_date', '<=', now()->addDays($days));
    }

    /**
     * Get the default package as a value object
     */
    public function getDefaultPackageObjectAttribute(): EmploymentPackage
    {
        return EmploymentPackage::fromArray($this->default_package);
    }

    /**
     * Check if permission is expiring within 30 days
     */
    public function getPermissionExpiringAttribute(): bool
    {
        if (!$this->permission_expiry_date) {
            return false;
        }
        return $this->permission_expiry_date->isFuture()
            && $this->permission_expiry_date->diffInDays(now()) <= 30;
    }

    /**
     * Check if permission has expired
     */
    public function getPermissionExpiredAttribute(): bool
    {
        if (!$this->permission_expiry_date) {
            return false;
        }
        return $this->permission_expiry_date->isPast();
    }

    /**
     * Get active candidate count
     */
    public function getActiveCandidateCountAttribute(): int
    {
        return $this->activeCandidates()->count();
    }

    /**
     * Verify the employer
     */
    public function verify(): void
    {
        $this->verified = true;
        $this->verified_at = now();
        $this->verified_by = auth()->id();
        $this->save();

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->log('Employer verified');
    }

    /**
     * Assign a candidate to this employer
     */
    public function assignCandidate(
        Candidate $candidate,
        string $employmentType = 'initial',
        ?array $customPackage = null
    ): void {
        $this->candidates()->attach($candidate->id, [
            'employment_type' => $employmentType,
            'assignment_date' => now()->toDateString(),
            'custom_package' => $customPackage ? json_encode($customPackage) : null,
            'status' => 'active',
            'is_current' => true,
            'assigned_at' => now(),
            'assigned_by' => auth()->id(),
        ]);

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['candidate_id' => $candidate->id])
            ->log('Candidate assigned to employer');
    }

    /**
     * Get the employment package for a specific candidate
     */
    public function getPackageForCandidate(Candidate $candidate): EmploymentPackage
    {
        $pivot = $this->candidates()->where('candidate_id', $candidate->id)->first()?->pivot;

        if ($pivot && $pivot->custom_package) {
            return EmploymentPackage::fromArray(json_decode($pivot->custom_package, true));
        }

        return $this->default_package_object;
    }
}
