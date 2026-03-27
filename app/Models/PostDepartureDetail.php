<?php

namespace App\Models;

use App\Enums\IqamaStatus;
use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PostDepartureDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'departure_id',
        // Iqama
        'iqama_number',
        'iqama_issue_date',
        'iqama_expiry_date',
        'iqama_evidence_path',
        'iqama_status',
        // Legacy residency fields
        'residency_proof_path',
        'residency_number',
        'residency_expiry',
        // Foreign License
        'foreign_license_number',
        'foreign_license_type',
        'foreign_license_expiry',
        'foreign_license_path',
        // Foreign Contact
        'foreign_mobile_number',
        'foreign_mobile_carrier',
        'foreign_address',
        // Foreign Bank
        'foreign_bank_name',
        'foreign_bank_account',
        'foreign_bank_iban',
        'foreign_bank_swift',
        'foreign_bank_evidence_path',
        // Tracking App
        'tracking_app_registration',
        'tracking_app_name',
        'tracking_app_id',
        'tracking_app_registered',
        'tracking_app_registered_date',
        'tracking_app_evidence_path',
        // WPS
        'wps_registered',
        'wps_registration_date',
        'wps_evidence_path',
        // Contract
        'final_contract_path',
        'contract_number',
        'contract_start_date',
        'contract_end_date',
        'contract_evidence_path',
        'contract_status',
        // Legacy employment fields
        'company_name',
        'employer_name',
        'employer_designation',
        'employer_contact',
        'work_location',
        'final_salary',
        'salary_currency',
        'final_job_terms',
        'job_commencement_date',
        'special_conditions',
        // Compliance
        'compliance_verified',
        'compliance_verified_date',
        'compliance_verified_by',
    ];

    protected $casts = [
        'iqama_issue_date' => 'date',
        'iqama_expiry_date' => 'date',
        'iqama_status' => IqamaStatus::class,
        'residency_expiry' => 'date',
        'foreign_license_expiry' => 'date',
        'tracking_app_registered' => 'boolean',
        'tracking_app_registered_date' => 'date',
        'wps_registered' => 'boolean',
        'wps_registration_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'contract_status' => ContractStatus::class,
        'compliance_verified' => 'boolean',
        'compliance_verified_date' => 'date',
        'final_salary' => 'decimal:2',
        'job_commencement_date' => 'date',
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function departure()
    {
        return $this->belongsTo(Departure::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'compliance_verified_by');
    }

    public function employmentHistory()
    {
        return $this->hasMany(EmploymentHistory::class)->orderBy('sequence');
    }

    public function currentEmployment()
    {
        return $this->hasOne(EmploymentHistory::class)->where('status', 'current');
    }

    public function companySwitches()
    {
        return $this->hasManyThrough(
            CompanySwitchLog::class,
            Candidate::class,
            'id',
            'candidate_id',
            'candidate_id',
            'id'
        );
    }

    // -----------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------

    public function getIqamaExpiringAttribute(): bool
    {
        if (!$this->iqama_expiry_date) return false;
        return $this->iqama_expiry_date->diffInDays(now()) <= 30;
    }

    public function getTotalSalaryAttribute(): float
    {
        $employment = $this->currentEmployment;
        if (!$employment) return 0;

        return ($employment->base_salary ?? 0)
            + ($employment->housing_allowance ?? 0)
            + ($employment->food_allowance ?? 0)
            + ($employment->transport_allowance ?? 0)
            + ($employment->other_allowance ?? 0);
    }

    public function getSwitchCountAttribute(): int
    {
        return $this->employmentHistory()->count() - 1;
    }

    // -----------------------------------------------------------------------
    // Methods
    // -----------------------------------------------------------------------

    public function updateIqama(array $data, $evidenceFile = null): void
    {
        if ($evidenceFile) {
            $data['iqama_evidence_path'] = $this->uploadFile($evidenceFile, 'iqama');
        }

        $this->fill($data);
        $this->save();

        $this->logActivity('Iqama details updated', $data);
    }

    public function updateForeignContact(array $data): void
    {
        $this->fill([
            'foreign_mobile_number' => $data['mobile_number'] ?? $this->foreign_mobile_number,
            'foreign_mobile_carrier' => $data['carrier'] ?? $this->foreign_mobile_carrier,
            'foreign_address' => $data['address'] ?? $this->foreign_address,
        ]);
        $this->save();

        $this->logActivity('Foreign contact updated');
    }

    public function updateForeignBank(array $data, $evidenceFile = null): void
    {
        if ($evidenceFile) {
            $data['foreign_bank_evidence_path'] = $this->uploadFile($evidenceFile, 'bank');
        }

        $this->fill([
            'foreign_bank_name' => $data['bank_name'] ?? null,
            'foreign_bank_account' => $data['account_number'] ?? null,
            'foreign_bank_iban' => $data['iban'] ?? null,
            'foreign_bank_swift' => $data['swift'] ?? null,
            'foreign_bank_evidence_path' => $data['foreign_bank_evidence_path'] ?? $this->foreign_bank_evidence_path,
        ]);
        $this->save();

        $this->logActivity('Foreign bank details updated');
    }

    public function registerTrackingApp(string $appName, string $appId, $evidenceFile = null): void
    {
        $evidencePath = $evidenceFile ? $this->uploadFile($evidenceFile, 'tracking-app') : null;

        $this->fill([
            'tracking_app_name' => $appName,
            'tracking_app_id' => $appId,
            'tracking_app_registered' => true,
            'tracking_app_registered_date' => now(),
            'tracking_app_evidence_path' => $evidencePath,
        ]);
        $this->save();

        $this->logActivity("Registered on {$appName}", ['app_id' => $appId]);
    }

    public function updateContract(array $data, $contractFile = null): void
    {
        if ($contractFile) {
            $data['contract_evidence_path'] = $this->uploadFile($contractFile, 'contract');
        }

        $this->fill([
            'contract_number' => $data['contract_number'] ?? $this->contract_number,
            'contract_start_date' => $data['start_date'] ?? $this->contract_start_date,
            'contract_end_date' => $data['end_date'] ?? $this->contract_end_date,
            'contract_evidence_path' => $data['contract_evidence_path'] ?? $this->contract_evidence_path,
            'contract_status' => $data['status'] ?? $this->contract_status,
        ]);
        $this->save();

        $this->logActivity('Contract details updated');
    }

    public function markComplianceVerified(): void
    {
        $this->compliance_verified = true;
        $this->compliance_verified_date = now();
        $this->compliance_verified_by = auth()->id();
        $this->save();

        $this->candidate->update(['status' => 'post_departure']);

        $this->logActivity('90-day compliance verified');
    }

    public function getComplianceChecklist(): array
    {
        return [
            'iqama' => [
                'label' => 'Iqama/Residency',
                'complete' => $this->iqama_status === IqamaStatus::ISSUED,
                'expiring' => $this->iqama_expiring,
            ],
            'tracking_app' => [
                'label' => 'Tracking App (Absher)',
                'complete' => $this->tracking_app_registered,
            ],
            'wps' => [
                'label' => 'WPS Registration',
                'complete' => $this->wps_registered,
            ],
            'contract' => [
                'label' => 'Employment Contract',
                'complete' => $this->contract_status === ContractStatus::ACTIVE,
            ],
            'bank' => [
                'label' => 'Foreign Bank Account',
                'complete' => !empty($this->foreign_bank_account),
            ],
            'contact' => [
                'label' => 'Foreign Contact Details',
                'complete' => !empty($this->foreign_mobile_number),
            ],
        ];
    }

    public function uploadFile($file, string $subfolder): string
    {
        $candidateId = $this->candidate_id;
        $timestamp = now()->format('Y-m-d_His');
        $extension = $file->getClientOriginalExtension();
        $filename = "{$subfolder}_{$candidateId}_{$timestamp}.{$extension}";

        return $file->storeAs(
            "post-departure/{$candidateId}/{$subfolder}",
            $filename,
            'private'
        );
    }

    protected function logActivity(string $message, array $properties = []): void
    {
        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->log($message);
    }
}
