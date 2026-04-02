<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\PostDepartureDetail;
use App\Models\EmploymentHistory;
use App\Models\CompanySwitchLog;
use App\Enums\EmploymentStatus;
use Illuminate\Support\Facades\DB;

class PostDepartureService
{
    /**
     * Get or create post departure details for candidate.
     */
    public function getOrCreateDetails(Candidate $candidate): PostDepartureDetail
    {
        return PostDepartureDetail::firstOrCreate(
            ['candidate_id' => $candidate->id],
            ['departure_id' => $candidate->departure?->id]
        );
    }

    /**
     * Update iqama/residency details.
     */
    public function updateIqama(PostDepartureDetail $detail, array $data, $evidenceFile = null): void
    {
        $detail->updateIqama($data, $evidenceFile);
    }

    /**
     * Update foreign license.
     */
    public function updateForeignLicense(PostDepartureDetail $detail, array $data, $licenseFile = null): void
    {
        if ($licenseFile) {
            $data['foreign_license_path'] = $detail->uploadFile($licenseFile, 'license');
        }

        $detail->fill([
            'foreign_license_number' => $data['license_number'] ?? null,
            'foreign_license_type' => $data['license_type'] ?? null,
            'foreign_license_expiry' => $data['expiry_date'] ?? null,
            'foreign_license_path' => $data['foreign_license_path'] ?? $detail->foreign_license_path,
        ]);
        $detail->save();
    }

    /**
     * Update foreign contact details.
     */
    public function updateForeignContact(PostDepartureDetail $detail, array $data): void
    {
        $detail->updateForeignContact($data);
    }

    /**
     * Update foreign bank details.
     */
    public function updateForeignBank(PostDepartureDetail $detail, array $data, $evidenceFile = null): void
    {
        $detail->updateForeignBank($data, $evidenceFile);
    }

    /**
     * Register tracking app (Absher).
     */
    public function registerTrackingApp(
        PostDepartureDetail $detail,
        string $appName,
        string $appId,
        $evidenceFile = null
    ): void {
        $detail->registerTrackingApp($appName, $appId, $evidenceFile);
    }

    /**
     * Register WPS.
     */
    public function registerWPS(PostDepartureDetail $detail, $evidenceFile = null): void
    {
        $evidencePath = $evidenceFile
            ? $detail->uploadFile($evidenceFile, 'wps')
            : null;

        $detail->fill([
            'wps_registered' => true,
            'wps_registration_date' => now(),
            'wps_evidence_path' => $evidencePath,
        ]);
        $detail->save();

        activity()
            ->performedOn($detail)
            ->causedBy(auth()->user())
            ->log('WPS registered');
    }

    /**
     * Update employment contract (Qiwa).
     */
    public function updateContract(PostDepartureDetail $detail, array $data, $contractFile = null): void
    {
        $detail->updateContract($data, $contractFile);
    }

    /**
     * Add initial employment.
     */
    public function addInitialEmployment(PostDepartureDetail $detail, array $data, $contractFile = null): EmploymentHistory
    {
        return DB::transaction(function () use ($detail, $data, $contractFile) {
            $contractPath = $contractFile
                ? $detail->uploadFile($contractFile, 'employment-contract')
                : null;

            $employment = EmploymentHistory::create([
                'candidate_id' => $detail->candidate_id,
                'post_departure_detail_id' => $detail->id,
                'departure_id' => $detail->departure_id,
                'employer_id' => $data['employer_id'] ?? null,
                'company_name' => $data['company_name'],
                'company_address' => $data['company_address'] ?? null,
                'employer_contact_name' => $data['contact_name'] ?? null,
                'employer_contact_phone' => $data['contact_phone'] ?? null,
                'employer_contact_email' => $data['contact_email'] ?? null,
                'position_title' => $data['position_title'] ?? null,
                'department' => $data['department'] ?? null,
                'work_location' => $data['work_location'] ?? null,
                'base_salary' => $data['base_salary'] ?? null,
                'salary_currency' => $data['currency'] ?? 'SAR',
                'housing_allowance' => $data['housing_allowance'] ?? null,
                'food_allowance' => $data['food_allowance'] ?? null,
                'transport_allowance' => $data['transport_allowance'] ?? null,
                'other_allowance' => $data['other_allowance'] ?? null,
                'benefits' => $data['benefits'] ?? null,
                'commencement_date' => $data['commencement_date'],
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'contract_path' => $contractPath,
                'status' => EmploymentStatus::CURRENT,
                'sequence' => 1,
                'switch_number' => 0,
                'switch_date' => $data['commencement_date'],
                'recorded_by' => auth()->id(),
            ]);

            activity()
                ->performedOn($employment)
                ->causedBy(auth()->user())
                ->log('Initial employment recorded');

            return $employment;
        });
    }

    /**
     * Initiate company switch.
     */
    public function initiateCompanySwitch(
        PostDepartureDetail $detail,
        array $newEmploymentData,
        string $reason,
        $releaseLetterFile = null,
        $newContractFile = null
    ): CompanySwitchLog {
        return DB::transaction(function () use ($detail, $newEmploymentData, $reason, $releaseLetterFile, $newContractFile) {
            $currentEmployment = $detail->currentEmployment;

            if (!$currentEmployment) {
                throw new \Exception('No current employment found to switch from.');
            }

            $switchCount = CompanySwitchLog::where('candidate_id', $detail->candidate_id)
                ->whereIn('status', ['approved', 'completed'])
                ->count();

            if ($switchCount >= 2) {
                throw new \Exception('Maximum of 2 company switches allowed.');
            }

            $switchNumber = $switchCount + 1;

            // Create new employment record (pending)
            $newEmployment = EmploymentHistory::create(array_merge($newEmploymentData, [
                'candidate_id' => $detail->candidate_id,
                'post_departure_detail_id' => $detail->id,
                'departure_id' => $detail->departure_id,
                'sequence' => $currentEmployment->sequence + 1,
                'switch_number' => $switchNumber,
                'switch_date' => now()->toDateString(),
                'recorded_by' => auth()->id(),
                'status' => EmploymentStatus::PREVIOUS, // Will become current when switch completes
            ]));

            // Upload documents
            $releaseLetterPath = $releaseLetterFile
                ? $detail->uploadFile($releaseLetterFile, 'switch/release-letter')
                : null;
            $newContractPath = $newContractFile
                ? $detail->uploadFile($newContractFile, 'switch/contract')
                : null;

            // Create switch log
            $switchLog = CompanySwitchLog::create([
                'candidate_id' => $detail->candidate_id,
                'from_employment_id' => $currentEmployment->id,
                'to_employment_id' => $newEmployment->id,
                'switch_number' => $switchNumber,
                'switch_date' => now(),
                'reason' => $reason,
                'status' => 'pending',
                'release_letter_path' => $releaseLetterPath,
                'new_contract_path' => $newContractPath,
            ]);

            activity()
                ->performedOn($switchLog)
                ->causedBy(auth()->user())
                ->withProperties([
                    'switch_number' => $switchNumber,
                    'from_company' => $currentEmployment->company_name,
                    'to_company' => $newEmploymentData['company_name'],
                ])
                ->log("Company switch #{$switchNumber} initiated");

            return $switchLog;
        });
    }

    /**
     * Approve company switch.
     */
    public function approveCompanySwitch(CompanySwitchLog $switch, ?string $notes = null, $approvalDoc = null): void
    {
        if ($approvalDoc) {
            $detail = PostDepartureDetail::where('candidate_id', $switch->candidate_id)->first();
            $switch->approval_document_path = $detail->uploadFile($approvalDoc, 'switch/approval');
            $switch->save();
        }

        $switch->approve($notes);
    }

    /**
     * Complete company switch.
     */
    public function completeCompanySwitch(CompanySwitchLog $switch): void
    {
        if ($switch->status->value !== 'approved') {
            throw new \Exception('Switch must be approved before completion.');
        }

        $switch->complete();
    }

    /**
     * Verify 90-day compliance.
     */
    public function verifyCompliance(PostDepartureDetail $detail): void
    {
        $checklist = $detail->getComplianceChecklist();
        $incomplete = collect($checklist)->filter(fn($item) => !$item['complete']);

        if ($incomplete->isNotEmpty()) {
            $missing = $incomplete->pluck('label')->join(', ');
            throw new \Exception("Cannot verify compliance. Missing: {$missing}");
        }

        $detail->markComplianceVerified();
    }

    /**
     * Get post-departure dashboard data.
     */
    public function getDashboard(?int $campusId = null): array
    {
        $query = PostDepartureDetail::with([
            'candidate.campus',
            'candidate.trade',
            'currentEmployment',
        ]);

        if ($campusId) {
            $query->whereHas('candidate', fn($q) => $q->where('campus_id', $campusId));
        }

        $details = $query->get();

        return [
            'summary' => [
                'total' => $details->count(),
                'compliance_verified' => $details->where('compliance_verified', true)->count(),
                'compliance_pending' => $details->where('compliance_verified', false)->count(),
            ],
            'iqama_status' => [
                'pending' => $details->filter(fn($d) => $d->iqama_status?->value === 'pending')->count(),
                'issued' => $details->filter(fn($d) => $d->iqama_status?->value === 'issued')->count(),
                'expiring' => $details->filter(fn($d) => $d->iqama_expiring)->count(),
            ],
            'tracking_app' => [
                'registered' => $details->where('tracking_app_registered', true)->count(),
                'pending' => $details->where('tracking_app_registered', false)->count(),
            ],
            'wps' => [
                'registered' => $details->where('wps_registered', true)->count(),
                'pending' => $details->where('wps_registered', false)->count(),
            ],
            'recent_switches' => CompanySwitchLog::with(['candidate', 'fromEmployment', 'toEmployment'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'candidates' => $details,
        ];
    }
}
