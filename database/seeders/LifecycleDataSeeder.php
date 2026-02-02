<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Program;
use App\Models\ImplementingPartner;
use App\Models\Oep;
use App\Models\Batch;
use App\Models\Country;
use App\Models\Candidate;
use App\Models\CandidateScreening;
use App\Models\TrainingAssessment;
use App\Models\TrainingCertificate;
use App\Models\VisaProcess;
use App\Models\Departure;
use App\Models\PostDepartureDetail;
use App\Models\SuccessStory;
use App\Enums\CandidateStatus;
use App\Enums\ScreeningStatus;
use App\Enums\PlacementInterest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds comprehensive lifecycle data for development and testing.
 * Creates candidates at each stage of the WASL workflow.
 */
class LifecycleDataSeeder extends Seeder
{
    protected User $admin;
    protected Campus $campus;
    protected Trade $trade;
    protected Program $program;
    protected ImplementingPartner $partner;
    protected Oep $oep;
    protected Batch $batch;
    protected Country $country;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Lifecycle Data Seeder...');
        
        // Create base dependencies
        $this->createBaseDependencies();

        // Create candidates at each lifecycle stage
        $this->createListedCandidates();
        $this->createPreDepartureDocsCandidates();
        $this->createScreeningCandidates();
        $this->createScreenedCandidates();
        $this->createRegisteredCandidates();
        $this->createTrainingCandidates();
        $this->createTrainingCompletedCandidates();
        $this->createVisaProcessCandidates();
        $this->createVisaApprovedCandidates();
        $this->createDepartureProcessingCandidates();
        $this->createReadyToDepartCandidates();
        $this->createDepartedCandidates();
        $this->createPostDepartureCandidates();
        $this->createCompletedCandidates();
        $this->createTerminalStateCandidates();

        $this->command->info('Lifecycle Data Seeder completed!');
        $this->command->table(
            ['Status', 'Count'],
            collect(CandidateStatus::cases())
                ->map(fn($status) => [$status->label(), Candidate::where('status', $status->value)->count()])
                ->toArray()
        );
    }

    protected function createBaseDependencies(): void
    {
        $this->command->info('Creating base dependencies...');

        // Admin user
        $this->admin = User::firstOrCreate(
            ['email' => 'admin@wasl.test'],
            [
                'name' => 'WASL Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        // Campus
        $this->campus = Campus::firstOrCreate(
            ['code' => 'LHR'],
            [
                'name' => 'Lahore Campus',
                'district' => 'Lahore',
                'address' => '123 Main Street, Lahore',
                'phone' => '042-12345678',
                'email' => 'lahore@wasl.test',
            ]
        );

        // Trade
        $this->trade = Trade::firstOrCreate(
            ['code' => 'ELEC'],
            [
                'name' => 'Electrician',
                'description' => 'Electrical installation and maintenance',
                'is_active' => true,
            ]
        );

        // Program
        $this->program = Program::firstOrCreate(
            ['code' => 'KSAWP'],
            [
                'name' => 'Saudi Arabia Worker Program',
                'description' => 'Skilled worker program for Saudi Arabia',
                'is_active' => true,
            ]
        );

        // Implementing Partner
        $this->partner = ImplementingPartner::firstOrCreate(
            ['name' => 'Skills Development Center'],
            [
                'contact_person' => 'Ali Khan',
                'contact_email' => 'ali@skills.test',
                'phone' => '0300-1234567',
                'is_active' => true,
            ]
        );

        // OEP
        $this->oep = Oep::firstOrCreate(
            ['license_number' => 'OEP-2026-001'],
            [
                'name' => 'Global Employment Services',
                'license_expiry' => now()->addYears(2),
                'contact_person' => 'Hassan Ali',
                'phone' => '0321-1234567',
                'email' => 'contact@globalemployment.test',
                'is_active' => true,
            ]
        );

        // Country
        $this->country = Country::firstOrCreate(
            ['code' => 'SA'],
            [
                'name' => 'Saudi Arabia',
                'is_active' => true,
            ]
        );

        // Batch
        $this->batch = Batch::firstOrCreate(
            ['batch_code' => 'LHR-KSAWP-ELEC-2026-0001'],
            [
                'name' => 'Lahore - Electrician - January 2026',
                'campus_id' => $this->campus->id,
                'trade_id' => $this->trade->id,
                'program_id' => $this->program->id,
                'oep_id' => $this->oep->id,
                'capacity' => 25,
                'status' => Batch::STATUS_ACTIVE,
                'start_date' => now()->subDays(30),
                'end_date' => now()->addDays(60),
            ]
        );
    }

    protected function createListedCandidates(): void
    {
        $this->createCandidatesForStatus(CandidateStatus::LISTED, 3);
    }

    protected function createPreDepartureDocsCandidates(): void
    {
        $this->createCandidatesForStatus(CandidateStatus::PRE_DEPARTURE_DOCS, 3);
    }

    protected function createScreeningCandidates(): void
    {
        $this->createCandidatesForStatus(CandidateStatus::SCREENING, 3);
    }

    protected function createScreenedCandidates(): void
    {
        $candidates = $this->createCandidatesForStatus(CandidateStatus::SCREENED, 3);
        foreach ($candidates as $candidate) {
            CandidateScreening::create([
                'candidate_id' => $candidate->id,
                'consent_for_work' => true,
                'placement_interest' => PlacementInterest::INTERNATIONAL->value,
                'target_country_id' => $this->country->id,
                'screening_status' => ScreeningStatus::SCREENED->value,
                'reviewed_by' => $this->admin->id,
                'reviewed_at' => now()->subDays(rand(1, 5)),
            ]);
        }
    }

    protected function createRegisteredCandidates(): void
    {
        $candidates = $this->createCandidatesForStatus(CandidateStatus::REGISTERED, 3);
        foreach ($candidates as $candidate) {
            $this->addScreening($candidate);
            $candidate->update([
                'batch_id' => $this->batch->id,
                'registration_date' => now()->subDays(rand(10, 20)),
            ]);
        }
    }

    protected function createTrainingCandidates(): void
    {
        $candidates = $this->createCandidatesForStatus(CandidateStatus::TRAINING, 3);
        foreach ($candidates as $candidate) {
            $this->addScreening($candidate);
            $candidate->update([
                'batch_id' => $this->batch->id,
                'registration_date' => now()->subDays(rand(30, 40)),
                'training_start_date' => now()->subDays(rand(20, 25)),
            ]);
        }
    }

    protected function createTrainingCompletedCandidates(): void
    {
        $candidates = $this->createCandidatesForStatus(CandidateStatus::TRAINING_COMPLETED, 3);
        foreach ($candidates as $candidate) {
            $this->addScreening($candidate);
            $candidate->update([
                'batch_id' => $this->batch->id,
                'registration_date' => now()->subDays(rand(60, 70)),
                'training_start_date' => now()->subDays(rand(50, 55)),
                'training_end_date' => now()->subDays(rand(5, 10)),
            ]);
            $this->addAssessments($candidate);
            $this->addCertificate($candidate);
        }
    }

    protected function createVisaProcessCandidates(): void
    {
        $candidates = $this->createCandidatesForStatus(CandidateStatus::VISA_PROCESS, 3);
        foreach ($candidates as $candidate) {
            $this->addFullTrainingHistory($candidate);
            VisaProcess::create([
                'candidate_id' => $candidate->id,
                'interview_date' => now()->subDays(rand(5, 10)),
                'interview_status' => 'completed',
            ]);
        }
    }

    protected function createVisaApprovedCandidates(): void
    {
        $candidates = $this->createCandidatesForStatus(CandidateStatus::VISA_APPROVED, 3);
        foreach ($candidates as $candidate) {
            $this->addFullTrainingHistory($candidate);
            VisaProcess::create([
                'candidate_id' => $candidate->id,
                'interview_date' => now()->subDays(rand(20, 30)),
                'interview_status' => 'passed',
                'medical_date' => now()->subDays(rand(15, 20)),
                'medical_status' => 'fit',
                'visa_number' => 'VISA-' . Str::random(8),
                'visa_status' => 'approved',
            ]);
        }
    }

    protected function createDepartureProcessingCandidates(): void
    {
        $candidates = $this->createCandidatesForStatus(CandidateStatus::DEPARTURE_PROCESSING, 3);
        foreach ($candidates as $candidate) {
            $this->addFullVisaHistory($candidate);
        }
    }

    protected function createReadyToDepartCandidates(): void
    {
        $candidates = $this->createCandidatesForStatus(CandidateStatus::READY_TO_DEPART, 3);
        foreach ($candidates as $candidate) {
            $this->addFullVisaHistory($candidate);
            Departure::create([
                'candidate_id' => $candidate->id,
                'departure_date' => now()->addDays(rand(1, 7)),
                'flight_number' => 'SV-' . rand(100, 999),
                'destination' => 'Riyadh',
            ]);
        }
    }

    protected function createDepartedCandidates(): void
    {
        $candidates = $this->createCandidatesForStatus(CandidateStatus::DEPARTED, 3);
        foreach ($candidates as $candidate) {
            $this->addFullVisaHistory($candidate);
            Departure::create([
                'candidate_id' => $candidate->id,
                'departure_date' => now()->subDays(rand(1, 7)),
                'flight_number' => 'SV-' . rand(100, 999),
                'destination' => 'Riyadh',
            ]);
        }
    }

    protected function createPostDepartureCandidates(): void
    {
        $candidates = $this->createCandidatesForStatus(CandidateStatus::POST_DEPARTURE, 3);
        foreach ($candidates as $candidate) {
            $this->addFullVisaHistory($candidate);
            $departure = Departure::create([
                'candidate_id' => $candidate->id,
                'departure_date' => now()->subDays(rand(30, 60)),
                'flight_number' => 'SV-' . rand(100, 999),
                'destination' => 'Riyadh',
            ]);
            PostDepartureDetail::create([
                'departure_id' => $departure->id,
                'residency_number' => 'RES-' . rand(100000000, 999999999),
                'company_name' => 'Saudi Company ' . rand(1, 100),
                'final_salary' => rand(1500, 3500),
                'salary_currency' => 'SAR',
            ]);
        }
    }

    protected function createCompletedCandidates(): void
    {
        $candidates = $this->createCandidatesForStatus(CandidateStatus::COMPLETED, 3);
        foreach ($candidates as $candidate) {
            $this->addFullVisaHistory($candidate);
            $departure = Departure::create([
                'candidate_id' => $candidate->id,
                'departure_date' => now()->subMonths(rand(6, 12)),
                'flight_number' => 'SV-' . rand(100, 999),
                'destination' => 'Riyadh',
            ]);
            PostDepartureDetail::create([
                'departure_id' => $departure->id,
                'residency_number' => 'RES-' . rand(100000000, 999999999),
                'company_name' => 'Saudi Company ' . rand(1, 100),
                'final_salary' => rand(2000, 4000),
                'salary_currency' => 'SAR',
            ]);
            SuccessStory::create([
                'candidate_id' => $candidate->id,
                'departure_id' => $departure->id,
                'written_note' => 'My journey has been transformative. I am now earning well and supporting my family.',
                'is_featured' => rand(0, 1) === 1,
                'recorded_by' => $this->admin->id,
                'recorded_at' => now()->subDays(rand(10, 30)),
            ]);
        }
    }

    protected function createTerminalStateCandidates(): void
    {
        // Deferred
        $this->createCandidatesForStatus(CandidateStatus::DEFERRED, 2);
        
        // Rejected
        $this->createCandidatesForStatus(CandidateStatus::REJECTED, 2);
        
        // Withdrawn
        $this->createCandidatesForStatus(CandidateStatus::WITHDRAWN, 2);
    }

    protected function createCandidatesForStatus(CandidateStatus $status, int $count): array
    {
        $this->command->info("Creating {$count} candidates with status: {$status->label()}");
        
        $candidates = [];
        for ($i = 0; $i < $count; $i++) {
            $candidates[] = Candidate::create([
                'btevta_id' => 'TLP-2026-' . str_pad(Candidate::count() + 1, 5, '0', STR_PAD_LEFT) . '-' . rand(0, 9),
                'cnic' => '35' . str_pad(rand(100000000, 999999999), 11, '0', STR_PAD_LEFT),
                'name' => fake()->name(),
                'father_name' => fake()->name('male'),
                'date_of_birth' => fake()->dateTimeBetween('-40 years', '-18 years'),
                'gender' => fake()->randomElement(['male', 'female']),
                'phone' => '03' . rand(0, 9) . rand(10000000, 99999999),
                'email' => fake()->unique()->safeEmail(),
                'address' => fake()->address(),
                'district' => fake()->city(),
                'campus_id' => $this->campus->id,
                'trade_id' => $this->trade->id,
                'status' => $status->value,
            ]);
        }
        
        return $candidates;
    }

    protected function addScreening(Candidate $candidate): void
    {
        CandidateScreening::firstOrCreate(
            ['candidate_id' => $candidate->id],
            [
                'consent_for_work' => true,
                'placement_interest' => PlacementInterest::INTERNATIONAL->value,
                'target_country_id' => $this->country->id,
                'screening_status' => ScreeningStatus::SCREENED->value,
                'reviewed_by' => $this->admin->id,
                'reviewed_at' => now()->subDays(rand(20, 40)),
            ]
        );
    }

    protected function addAssessments(Candidate $candidate): void
    {
        TrainingAssessment::firstOrCreate(
            ['candidate_id' => $candidate->id, 'assessment_type' => 'interim'],
            [
                'batch_id' => $this->batch->id,
                'assessment_date' => now()->subDays(rand(30, 40)),
                'score' => rand(60, 90),
                'max_score' => 100,
                'result' => 'pass',
            ]
        );
        
        TrainingAssessment::firstOrCreate(
            ['candidate_id' => $candidate->id, 'assessment_type' => 'final'],
            [
                'batch_id' => $this->batch->id,
                'assessment_date' => now()->subDays(rand(5, 15)),
                'score' => rand(65, 95),
                'max_score' => 100,
                'result' => 'pass',
            ]
        );
    }

    protected function addCertificate(Candidate $candidate): void
    {
        TrainingCertificate::firstOrCreate(
            ['candidate_id' => $candidate->id],
            [
                'batch_id' => $this->batch->id,
                'certificate_number' => 'CERT-' . now()->year . '-' . str_pad(TrainingCertificate::count() + 1, 5, '0', STR_PAD_LEFT),
                'issue_date' => now()->subDays(rand(5, 15)),
                'issued_by' => $this->admin->id,
            ]
        );
    }

    protected function addFullTrainingHistory(Candidate $candidate): void
    {
        $this->addScreening($candidate);
        $candidate->update([
            'batch_id' => $this->batch->id,
            'registration_date' => now()->subDays(rand(60, 90)),
            'training_start_date' => now()->subDays(rand(50, 60)),
            'training_end_date' => now()->subDays(rand(20, 30)),
        ]);
        $this->addAssessments($candidate);
        $this->addCertificate($candidate);
    }

    protected function addFullVisaHistory(Candidate $candidate): void
    {
        $this->addFullTrainingHistory($candidate);
        VisaProcess::firstOrCreate(
            ['candidate_id' => $candidate->id],
            [
                'interview_date' => now()->subDays(rand(20, 30)),
                'interview_status' => 'passed',
                'medical_date' => now()->subDays(rand(15, 20)),
                'medical_status' => 'fit',
                'visa_number' => 'VISA-' . Str::random(8),
                'visa_status' => 'approved',
                'ptn_number' => 'PTN-' . Str::random(8),
            ]
        );
    }
}
