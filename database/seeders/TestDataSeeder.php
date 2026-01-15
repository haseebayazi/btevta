<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Campus;
use App\Models\Oep;
use App\Models\Trade;
use App\Models\Batch;
use App\Models\Candidate;
use App\Models\CandidateScreening;
use App\Models\RegistrationDocument;
use App\Models\NextOfKin;
use App\Models\Undertaking;
use App\Models\VisaProcess;
use App\Models\Departure;
use App\Models\Complaint;
use App\Models\Correspondence;
use App\Models\Remittance;
use App\Models\DocumentArchive;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * AUDIT FIX: Added environment protection and secure password generation.
 * This seeder will refuse to run in production to prevent weak password creation.
 */
class TestDataSeeder extends Seeder
{
    /**
     * Store credentials for logging
     */
    private array $credentials = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // AUDIT FIX: Production environment protection
        if (app()->environment('production')) {
            $this->command->error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->command->error('🚨 SECURITY BLOCK: TestDataSeeder cannot run in production environment!');
            $this->command->error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->command->error('This seeder creates test users with generated passwords.');
            $this->command->error('Running it in production would create security vulnerabilities.');
            $this->command->error('');
            $this->command->error('If you need to create admin users in production, use:');
            $this->command->error('  php artisan admin:reset-password');
            $this->command->error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            return;
        }

        $this->command->info('Starting to seed test data...');
        $this->command->warn('⚠️  Environment: ' . app()->environment());

        // 1. Create Campuses FIRST (needed for user campus_id references)
        $campuses = $this->seedCampuses();
        $this->command->info('✓ Campuses created');

        // 2. Create Users (after campuses exist)
        $users = $this->seedUsers($campuses);
        $this->command->info('✓ Users created');

        // 3. Create OEPs
        $oeps = $this->seedOeps();
        $this->command->info('✓ OEPs created');

        // 4. Create Trades
        $trades = $this->seedTrades();
        $this->command->info('✓ Trades created');

        // 5. Create Batches
        $batches = $this->seedBatches($campuses, $trades);
        $this->command->info('✓ Batches created');

        // 6. Create Candidates at various stages
        $candidates = $this->seedCandidates($campuses, $trades, $oeps);
        $this->command->info('✓ Candidates created');

        // 7. Create Training records - SKIPPED (CandidateTraining model doesn't exist)
        // $this->seedTraining($candidates, $batches);
        // $this->command->info('✓ Training records created');

        // 8. Create Screening records
        $this->seedScreening($candidates);
        $this->command->info('✓ Screening records created');

        // 9. Create Registration data
        $this->seedRegistration($candidates);
        $this->command->info('✓ Registration data created');

        // 10. Create Visa Processing
        $this->seedVisaProcessing($candidates, $oeps);
        $this->command->info('✓ Visa processing created');

        // 11. Create Departures
        $this->seedDepartures($candidates);
        $this->command->info('✓ Departures created');

        // 12. Create Complaints
        $this->seedComplaints($candidates, $campuses, $oeps, $users);
        $this->command->info('✓ Complaints created');

        // 13. Create Correspondence
        $this->seedCorrespondence($campuses, $oeps);
        $this->command->info('✓ Correspondence created');

        // 14. Create Remittances
        $this->seedRemittances($candidates);
        $this->command->info('✓ Remittances created');

        // 15. Create Document Archive
        $this->seedDocumentArchive($candidates);
        $this->command->info('✓ Document archive created');

        // 16. Log credentials
        $this->logCredentials();

        $this->command->info('🎉 All test data seeded successfully!');

        // AUDIT FIX: Display generated passwords at the end
        if (!empty($this->generatedPasswords)) {
            $this->command->newLine();
            $this->command->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->command->warn('📋 GENERATED USER CREDENTIALS (SAVE THESE NOW!)');
            $this->command->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            foreach ($this->generatedPasswords as $email => $password) {
                $this->command->info("   {$email} => {$password}");
            }
            $this->command->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->command->warn('⚠️  These passwords will not be shown again!');
            $this->command->newLine();
        }
    }

    /**
     * Log seeded credentials to file and display in terminal
     */
    private function logCredentials(): void
    {
        if (empty($this->credentials)) {
            return;
        }

        $logPath = storage_path('logs/seeder-credentials.log');
        $content = "=== TheLeap Test Data Seeder Credentials ===\n";
        $content .= "Generated: " . now()->toDateTimeString() . "\n";
        $content .= "Environment: " . app()->environment() . "\n";
        $content .= str_repeat('=', 50) . "\n\n";

        $this->command->newLine();
        $this->command->warn('╔══════════════════════════════════════════════════════════════╗');
        $this->command->warn('║          SEEDED ACCOUNT CREDENTIALS                          ║');
        $this->command->warn('║   ⚠️  SAVE THESE CREDENTIALS - SHOWN ONLY ONCE ⚠️             ║');
        $this->command->warn('╚══════════════════════════════════════════════════════════════╝');
        $this->command->newLine();

        foreach ($this->credentials as $cred) {
            $content .= "Role: {$cred['role']}\n";
            $content .= "Name: {$cred['name']}\n";
            $content .= "Email: {$cred['email']}\n";
            $content .= "Password: {$cred['password']}\n";
            $content .= str_repeat('-', 40) . "\n\n";

            $this->command->line("  <fg=cyan>{$cred['role']}</>");
            $this->command->line("  Email:    <fg=green>{$cred['email']}</>");
            $this->command->line("  Password: <fg=yellow>{$cred['password']}</>");
            $this->command->newLine();
        }

        $content .= "\n⚠️  SECURITY WARNING: Delete this file after noting the credentials!\n";
        $content .= "File: {$logPath}\n";

        file_put_contents($logPath, $content);

        $this->command->warn("Credentials saved to: {$logPath}");
        $this->command->warn("⚠️  DELETE THIS FILE AFTER SAVING THE CREDENTIALS!");
        $this->command->newLine();
    }

    private function seedUsers($campuses)
    {
        $users = [];

        // Generate secure password for admin
        $adminPassword = Str::random(12);

        // Admin user - use firstOrCreate to avoid duplicate errors
        $adminUser = User::where('email', 'admin@theleap.org')->first();
        $isNewAdmin = !$adminUser;

        $users['admin'] = User::firstOrCreate(
            ['email' => 'admin@theleap.org'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        if ($users['admin']->wasRecentlyCreated) {
            $this->generatedPasswords['admin@theleap.org'] = $adminPassword;
        }

        if ($isNewAdmin) {
            $this->credentials[] = [
                'role' => 'Admin',
                'name' => 'System Administrator',
                'email' => 'admin@theleap.org',
                'password' => $adminPassword,
            ];
        }

        // Campus admins (one for each major campus)
        $campusAdmins = [
            ['name' => 'Lahore Campus Admin', 'email' => 'lahore@theleap.org', 'campus_index' => 0],
            ['name' => 'Karachi Campus Admin', 'email' => 'karachi@theleap.org', 'campus_index' => 1],
            ['name' => 'Islamabad Campus Admin', 'email' => 'islamabad@theleap.org', 'campus_index' => 2],
        ];

        foreach ($campusAdmins as $index => $admin) {
            $existingUser = User::where('email', $admin['email'])->first();
            $isNewUser = !$existingUser;
            $password = Str::random(12);

            $users["campus_admin_$index"] = User::firstOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make($password),
                    'role' => 'campus_admin',
                    'campus_id' => $campuses[$admin['campus_index']]->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            if ($isNewUser) {
                $this->credentials[] = [
                    'role' => 'Campus Admin',
                    'name' => $admin['name'],
                    'email' => $admin['email'],
                    'password' => $password,
                ];
            }
        }

        // Regular users
        $regularUsers = [
            ['name' => 'Muhammad Ahmed', 'email' => 'ahmed@theleap.org', 'campus_index' => 0],
            ['name' => 'Fatima Khan', 'email' => 'fatima@theleap.org', 'campus_index' => 1],
            ['name' => 'Ali Raza', 'email' => 'ali@theleap.org', 'campus_index' => 2],
        ];

        foreach ($regularUsers as $index => $userData) {
            $existingUser = User::where('email', $userData['email'])->first();
            $isNewUser = !$existingUser;
            $password = Str::random(12);

            $users["user" . ($index + 1)] = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($password),
                    'role' => 'user',
                    'campus_id' => $campuses[$userData['campus_index']]->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            if ($isNewUser) {
                $this->credentials[] = [
                    'role' => 'User',
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => $password,
                ];
            }
        }

        return $users;
    }

    /**
     * Generate a secure random password for test users.
     * AUDIT FIX: Replaces hardcoded 'password' with secure random passwords.
     */
    private function generateSecurePassword(): string
    {
        return Str::random(12) . rand(10, 99) . '!';
    }

    private function seedCampuses()
    {
        $campuses = [];

        // Use code as unique identifier (code is unique in DB schema)
        $campuses[] = Campus::firstOrCreate(
            ['code' => 'LHR-01'],
            [
                'name' => 'TheLeapLahore Campus',
                'address' => 'Main Boulevard, Gulberg III, Lahore, Punjab',
                'city' => 'Lahore',
                'contact_person' => 'Muhammad Rizwan',
                'phone' => '+92-42-35714567',
                'email' => 'lahore.campus@theleap.org',
                'is_active' => true,
            ]
        );

        $campuses[] = Campus::firstOrCreate(
            ['code' => 'KHI-01'],
            [
                'name' => 'TheLeapKarachi Campus',
                'address' => 'Block 5, Clifton, Karachi, Sindh',
                'city' => 'Karachi',
                'contact_person' => 'Ali Hassan',
                'phone' => '+92-21-35301234',
                'email' => 'karachi.campus@theleap.org',
                'is_active' => true,
            ]
        );

        $campuses[] = Campus::firstOrCreate(
            ['code' => 'ISB-01'],
            [
                'name' => 'TheLeapIslamabad Campus',
                'address' => 'G-11 Markaz, Islamabad',
                'city' => 'Islamabad',
                'contact_person' => 'Sana Malik',
                'phone' => '+92-51-2261234',
                'email' => 'islamabad.campus@theleap.org',
                'is_active' => true,
            ]
        );

        $campuses[] = Campus::firstOrCreate(
            ['code' => 'PSH-01'],
            [
                'name' => 'TheLeapPeshawar Campus',
                'address' => 'University Town, Peshawar, KP',
                'city' => 'Peshawar',
                'contact_person' => 'Asad Khan',
                'phone' => '+92-91-5701234',
                'email' => 'peshawar.campus@theleap.org',
                'is_active' => true,
            ]
        );

        return $campuses;
    }

    private function seedOeps()
    {
        $oeps = [];

        // Use name as unique identifier (name is unique in DB schema)
        $oeps[] = Oep::firstOrCreate(
            ['name' => 'Al-Khawarizmi Recruitment Services'],
            [
                'company_name' => 'Al-Khawarizmi Pvt. Ltd.',
                'registration_number' => 'REG-AK-2023-001',
                'contact_person' => 'Abdullah Mahmood',
                'phone' => '+92-300-1234567',
                'email' => 'info@alkhawarizmi.com.pk',
                'address' => 'Blue Area, Islamabad',
                'website' => 'https://alkhawarizmi.com.pk',
                'is_active' => true,
            ]
        );

        $oeps[] = Oep::firstOrCreate(
            ['name' => 'Gulf Manpower Solutions'],
            [
                'company_name' => 'Gulf Manpower Solutions Ltd.',
                'registration_number' => 'REG-GMS-2023-002',
                'contact_person' => 'Hamza Qureshi',
                'phone' => '+92-42-37000000',
                'email' => 'contact@gulfmanpower.pk',
                'address' => 'Main Boulevard, Lahore',
                'website' => 'https://gulfmanpower.pk',
                'is_active' => true,
            ]
        );

        $oeps[] = Oep::firstOrCreate(
            ['name' => 'Saudi Arabia Employment Agency'],
            [
                'company_name' => 'SAEA (Pvt) Ltd.',
                'registration_number' => 'REG-SAEA-2022-015',
                'contact_person' => 'Bilal Ahmed',
                'phone' => '+92-21-34500000',
                'email' => 'info@saeapk.gov.pk',
                'address' => 'I.I. Chundrigar Road, Karachi',
                'website' => null,
                'is_active' => true,
            ]
        );

        return $oeps;
    }

    private function seedTrades()
    {
        $trades = [];

        $tradesList = [
            ['code' => 'ELEC', 'name' => 'Electrician', 'duration_months' => 6],
            ['code' => 'PLMB', 'name' => 'Plumber', 'duration_months' => 6],
            ['code' => 'WELD', 'name' => 'Welder', 'duration_months' => 6],
            ['code' => 'CARP', 'name' => 'Carpenter', 'duration_months' => 6],
            ['code' => 'MECH', 'name' => 'Mechanic (Auto)', 'duration_months' => 6],
            ['code' => 'HVAC', 'name' => 'HVAC Technician', 'duration_months' => 6],
            ['code' => 'TILS', 'name' => 'Tile Setter', 'duration_months' => 4],
            ['code' => 'PAIN', 'name' => 'Painter', 'duration_months' => 4],
            ['code' => 'MASO', 'name' => 'Mason', 'duration_months' => 6],
            ['code' => 'DRIV', 'name' => 'Driver (Heavy Vehicle)', 'duration_months' => 3],
        ];

        foreach ($tradesList as $trade) {
            $trades[] = Trade::firstOrCreate(
                ['code' => $trade['code']],
                [
                    'name' => $trade['name'],
                    'duration_months' => $trade['duration_months'],
                    'description' => "Professional training in {$trade['name']} for overseas employment",
                    'is_active' => true,
                ]
            );
        }

        return $trades;
    }

    private function seedBatches($campuses, $trades)
    {
        $batches = [];

        // Create batches for different campuses and trades
        $batchData = [
            ['name' => 'Batch-2024-ELEC-01', 'trade' => 0, 'campus' => 0, 'start' => '-3 months', 'status' => 'completed'],
            ['name' => 'Batch-2024-PLMB-01', 'trade' => 1, 'campus' => 0, 'start' => '-2 months', 'status' => 'ongoing'],
            ['name' => 'Batch-2024-WELD-01', 'trade' => 2, 'campus' => 1, 'start' => '-1 month', 'status' => 'ongoing'],
            ['name' => 'Batch-2024-CARP-01', 'trade' => 3, 'campus' => 1, 'start' => '+1 week', 'status' => 'scheduled'],
            ['name' => 'Batch-2024-MECH-01', 'trade' => 4, 'campus' => 2, 'start' => '-4 months', 'status' => 'completed'],
            ['name' => 'Batch-2024-HVAC-01', 'trade' => 5, 'campus' => 2, 'start' => '-1 month', 'status' => 'ongoing'],
        ];

        foreach ($batchData as $data) {
            $startDate = now()->modify($data['start']);
            $endDate = (clone $startDate)->addMonths($trades[$data['trade']]->duration_months);

            $batches[] = Batch::firstOrCreate(
                ['name' => $data['name']],
                [
                    'description' => 'Training batch for ' . $trades[$data['trade']]->name,
                    'trade_id' => $trades[$data['trade']]->id,
                    'campus_id' => $campuses[$data['campus']]->id,
                    'oep_id' => null,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'capacity' => 30,
                    'status' => $data['status'],
                    'trainer_name' => 'Instructor ' . Str::random(5),
                ]
            );
        }

        return $batches;
    }

    private function seedCandidates($campuses, $trades, $oeps)
    {
        $candidates = [];

        // Different statuses to create realistic workflow
        $statuses = [
            'applied' => 5,
            'screening_pending' => 3,
            'screening_passed' => 4,
            'in_training' => 8,
            'training_completed' => 6,
            'registered' => 5,
            'visa_processing' => 4,
            'visa_approved' => 3,
            'departed' => 3,
        ];

        $counter = 1;
        foreach ($statuses as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $campusIndex = array_rand($campuses);
                $tradeIndex = array_rand($trades);
                $oepIndex = array_rand($oeps);

                $candidates[$status][] = Candidate::create([
                    'btevta_id' => 'BTV-' . date('Y') . '-' . str_pad($counter++, 5, '0', STR_PAD_LEFT),
                    'name' => $this->generateName(),
                    'father_name' => $this->generateName(),
                    'cnic' => $this->generateCNIC(),
                    'date_of_birth' => now()->subYears(rand(20, 35))->format('Y-m-d'),
                    'gender' => rand(0, 1) ? 'male' : 'female',
                    'phone' => '+92-3' . rand(10, 99) . '-' . rand(1000000, 9999999),
                    'email' => 'candidate' . $counter . '@example.com',
                    'address' => $this->generateAddress(),
                    'district' => $campuses[$campusIndex]->city,
                    'campus_id' => $campuses[$campusIndex]->id,
                    'trade_id' => $trades[$tradeIndex]->id,
                    'oep_id' => $oeps[$oepIndex]->id,
                    'status' => $status,
                    'application_id' => 'APP-' . date('Y') . '-' . str_pad($counter, 6, '0', STR_PAD_LEFT),
                ]);
            }
        }

        return $candidates;
    }

    private function seedTraining($candidates, $batches)
    {
        // Add training records for candidates in_training and training_completed
        if (isset($candidates['in_training'])) {
            foreach ($candidates['in_training'] as $index => $candidate) {
                $batch = $batches[min($index, count($batches) - 1)];

                CandidateTraining::create([
                    'candidate_id' => $candidate->id,
                    'batch_id' => $batch->id,
                    'enrollment_date' => $batch->start_date,
                    'attendance_percentage' => rand(75, 100),
                    'performance_score' => rand(60, 95),
                    'status' => 'ongoing',
                ]);
            }
        }

        if (isset($candidates['training_completed'])) {
            foreach ($candidates['training_completed'] as $index => $candidate) {
                $batch = $batches[min($index, count($batches) - 1)];

                CandidateTraining::create([
                    'candidate_id' => $candidate->id,
                    'batch_id' => $batch->id,
                    'enrollment_date' => $batch->start_date,
                    'completion_date' => $batch->end_date,
                    'attendance_percentage' => rand(85, 100),
                    'performance_score' => rand(75, 98),
                    'status' => 'completed',
                    'certificate_issued' => true,
                    'certificate_number' => 'CERT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                ]);
            }
        }
    }

    private function seedScreening($candidates)
    {
        // Add screening for screening_passed and beyond
        $screeningStatuses = ['screening_passed', 'in_training', 'training_completed', 'registered', 'visa_processing', 'visa_approved', 'departed'];

        foreach ($screeningStatuses as $status) {
            if (isset($candidates[$status])) {
                foreach ($candidates[$status] as $candidate) {
                    CandidateScreening::create([
                        'candidate_id' => $candidate->id,
                        'screening_type' => ['call', 'physical', 'document'][rand(0, 2)],
                        'screening_stage' => 3, // Stage 3 = Confirmation (tinyInteger: 1, 2, or 3)
                        'status' => 'passed',
                        'remarks' => 'Candidate qualified for training program',
                        'screened_by' => 1,
                        'screened_at' => now()->subDays(rand(10, 60)),
                        'call_count' => rand(1, 3),
                        'call_duration' => rand(300, 900),
                        'verification_status' => 'verified',
                    ]);
                }
            }
        }
    }

    private function seedRegistration($candidates)
    {
        // Add registration data for registered and beyond
        $registeredStatuses = ['registered', 'visa_processing', 'visa_approved', 'departed'];

        foreach ($registeredStatuses as $status) {
            if (isset($candidates[$status])) {
                foreach ($candidates[$status] as $candidate) {
                    // Documents
                    $docTypes = ['cnic', 'passport', 'education', 'police_clearance'];
                    foreach ($docTypes as $type) {
                        RegistrationDocument::create([
                            'candidate_id' => $candidate->id,
                            'document_type' => $type,
                            'document_number' => strtoupper(Str::random(10)),
                            'file_path' => 'candidates/documents/sample_' . $type . '.pdf',
                            'issue_date' => now()->subMonths(rand(6, 24)),
                            'expiry_date' => $type === 'passport' ? now()->addYears(5) : null,
                            'status' => 'valid',
                            'remarks' => 'Document verified and approved',
                        ]);
                    }

                    // Next of Kin
                    NextOfKin::create([
                        'candidate_id' => $candidate->id,
                        'name' => $this->generateName(),
                        'relationship' => ['father', 'mother', 'brother', 'spouse'][rand(0, 3)],
                        'cnic' => $this->generateCNIC(),
                        'phone' => '+92-3' . rand(10, 99) . '-' . rand(1000000, 9999999),
                        'address' => $this->generateAddress(),
                        'occupation' => ['Business', 'Government Employee', 'Private Employee', 'Self-Employed'][rand(0, 3)],
                    ]);

                    // Undertaking
                    Undertaking::create([
                        'candidate_id' => $candidate->id,
                        'undertaking_type' => 'employment',
                        'content' => 'I, ' . $candidate->name . ', hereby undertake to complete the training program and work in Saudi Arabia for the contracted period as per the terms and conditions.',
                        'signed_at' => now()->subDays(rand(5, 30)),
                        'is_completed' => 1,
                        'witness_name' => $this->generateName(),
                        'witness_cnic' => $this->generateCNIC(),
                    ]);
                }
            }
        }
    }

    private function seedVisaProcessing($candidates, $oeps)
    {
        $visaStatuses = ['visa_processing', 'visa_approved', 'departed'];

        foreach ($visaStatuses as $status) {
            if (isset($candidates[$status])) {
                foreach ($candidates[$status] as $candidate) {
                    $isApproved = in_array($status, ['visa_approved', 'departed']);

                    VisaProcess::create([
                        'candidate_id' => $candidate->id,
                        'interview_date' => $isApproved ? now()->subDays(rand(60, 90)) : null,
                        'interview_status' => $isApproved ? 'passed' : 'pending',
                        'trade_test_date' => $isApproved ? now()->subDays(rand(50, 70)) : null,
                        'trade_test_status' => $isApproved ? 'passed' : null,
                        'medical_date' => $isApproved ? now()->subDays(rand(40, 60)) : null,
                        'medical_status' => $isApproved ? 'fit' : null,
                        'biometric_date' => $isApproved ? now()->subDays(rand(30, 50)) : null,
                        'biometric_status' => $isApproved ? 'completed' : null,
                        'visa_date' => $isApproved ? now()->subDays(rand(10, 30)) : null,
                        'visa_number' => $isApproved ? 'VISA-' . strtoupper(Str::random(8)) : null,
                        'visa_status' => $isApproved ? 'approved' : 'pending',
                        'ticket_uploaded' => $status === 'departed',
                        'ticket_date' => $status === 'departed' ? now()->subDays(rand(1, 10)) : null,
                        'overall_status' => $isApproved ? ($status === 'departed' ? 'completed' : 'approved') : 'in_progress',
                        'remarks' => $isApproved ? 'All processes completed successfully' : 'Visa processing in progress',
                    ]);
                }
            }
        }
    }

    private function seedDepartures($candidates)
    {
        if (isset($candidates['departed'])) {
            foreach ($candidates['departed'] as $candidate) {
                $departureDate = now()->subDays(rand(10, 80));
                $is90DaysPlus = $departureDate->diffInDays(now()) > 90;

                Departure::create([
                    'candidate_id' => $candidate->id,
                    'departure_date' => $departureDate,
                    'flight_number' => 'PK-' . rand(100, 999),
                    'destination' => ['Riyadh', 'Jeddah', 'Dammam'][rand(0, 2)],
                    'pre_departure_briefing' => true,
                    'briefing_date' => $departureDate->copy()->subDays(3),
                    'iqama_number' => 'IQ-' . rand(10000000, 99999999),
                    'iqama_issue_date' => $departureDate->copy()->addDays(rand(7, 15)),
                    'absher_registered' => true,
                    'absher_registration_date' => $departureDate->copy()->addDays(rand(10, 20)),
                    'qiwa_id' => 'QW-' . rand(100000, 999999),
                    'qiwa_activated' => true,
                    'salary_amount' => rand(1500, 3000),
                    'first_salary_date' => $departureDate->copy()->addDays(rand(30, 45)),
                    'ninety_day_report_submitted' => $is90DaysPlus,
                    'remarks' => $is90DaysPlus ? '90-day compliance report submitted' : 'Candidate settled successfully',
                ]);
            }
        }
    }

    private function seedComplaints($candidates, $campuses, $oeps, $users)
    {
        $allCandidates = collect($candidates)->flatten(1)->take(10);

        foreach ($allCandidates as $index => $candidate) {
            if ($candidate && $index < 8) { // Create 8 sample complaints
                $isResolved = rand(0, 1);
                Complaint::create([
                    'candidate_id' => $candidate->id,
                    'campus_id' => $candidate->campus_id,
                    'oep_id' => $candidate->oep_id,
                    'subject' => 'Complaint regarding ' . ['salary payment', 'contract terms', 'accommodation', 'working hours'][rand(0, 3)],
                    'description' => 'Detailed complaint description regarding the issue faced by the candidate. Contact: ' . $candidate->name . ', Phone: ' . $candidate->phone,
                    'status' => $isResolved ? 'resolved' : ['open', 'investigating'][rand(0, 1)],
                    'complaint_date' => now()->subDays(rand(1, 60)),
                    'resolution_date' => $isResolved ? now()->subDays(rand(1, 30)) : null,
                    'resolution_notes' => $isResolved ? 'Complaint has been resolved successfully. All necessary actions have been taken.' : null,
                ]);
            }
        }
    }

    private function seedCorrespondence($campuses, $oeps)
    {
        for ($i = 0; $i < 10; $i++) {
            $hasReply = rand(0, 1);
            Correspondence::create([
                'campus_id' => $i % 2 == 0 ? $campuses[array_rand($campuses)]->id : null,
                'oep_id' => $i % 2 != 0 ? $oeps[array_rand($oeps)]->id : null,
                'subject' => 'Subject of correspondence ' . ($i + 1),
                'message' => 'Brief summary of the correspondence content and main points discussed for correspondence number ' . ($i + 1) . '.',
                'sent_at' => now()->subDays(rand(1, 90)),
                'replied' => $hasReply,
                'replied_at' => $hasReply ? now()->subDays(rand(1, 30)) : null,
                'status' => $hasReply ? 'replied' : 'pending',
                'requires_reply' => true,
            ]);
        }
    }

    private function seedRemittances($candidates)
    {
        if (isset($candidates['departed'])) {
            foreach ($candidates['departed'] as $candidate) {
                for ($i = 0; $i < rand(1, 3); $i++) {
                    $amountPKR = rand(50000, 200000);
                    $exchangeRate = rand(4, 5) + (rand(0, 99) / 100);
                    $amountSAR = round($amountPKR / $exchangeRate, 2);
                    $transferDate = now()->subDays(rand(30, 180));

                    Remittance::create([
                        'candidate_id' => $candidate->id,
                        'departure_id' => null,
                        'recorded_by' => null,
                        'transaction_reference' => 'TXN-' . strtoupper(Str::random(10)),
                        'amount' => $amountPKR,
                        'currency' => 'PKR',
                        'amount_foreign' => $amountSAR,
                        'foreign_currency' => 'SAR',
                        'exchange_rate' => $exchangeRate,
                        'transfer_date' => $transferDate,
                        'year' => $transferDate->year,      // Required column
                        'month' => $transferDate->month,    // Required column
                        'transfer_method' => ['Bank Transfer', 'Money Exchange', 'Digital Wallet'][rand(0, 2)],
                        'sender_name' => $candidate->name,
                        'sender_location' => ['Riyadh', 'Jeddah', 'Dammam'][rand(0, 2)],
                        'receiver_name' => $candidate->father_name,
                        'receiver_account' => rand(1000000000, 9999999999),
                        'bank_name' => ['HBL', 'MCB', 'UBL', 'Allied Bank'][rand(0, 3)],
                        'primary_purpose' => ['education', 'health', 'food', 'rent'][rand(0, 3)],
                    ]);
                }
            }
        }
    }

    private function seedDocumentArchive($candidates)
    {
        $allCandidates = collect($candidates)->flatten(1)->take(15);

        foreach ($allCandidates as $candidate) {
            if ($candidate) {
                for ($i = 0; $i < rand(1, 3); $i++) {
                    DocumentArchive::create([
                        'candidate_id' => $candidate->id,
                        'document_type' => ['contract', 'certificate', 'letter', 'report', 'other'][rand(0, 4)],
                        'document_name' => 'Document_' . Str::random(5) . '.pdf',
                        'file_path' => 'archive/documents/sample_' . Str::random(8) . '.pdf',
                        'upload_date' => now()->subDays(rand(1, 180)),
                        'expiry_date' => rand(0, 1) ? now()->addMonths(rand(6, 24)) : null,
                        'version' => '1',
                    ]);
                }
            }
        }
    }

    // Helper methods
    private function generateName()
    {
        $firstNames = ['Muhammad', 'Ali', 'Ahmed', 'Hassan', 'Bilal', 'Usman', 'Hamza', 'Abdullah', 'Zain', 'Fahad'];
        $lastNames = ['Khan', 'Ahmed', 'Ali', 'Hassan', 'Hussain', 'Malik', 'Iqbal', 'Shah', 'Raza', 'Mahmood'];
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    private function generateCNIC()
    {
        // Generate 13-digit CNIC without dashes (database stores as varchar(13))
        return rand(10000, 99999) . str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT) . rand(1, 9);
    }

    private function generateAddress()
    {
        $streets = ['Main Road', 'Canal Road', 'Mall Road', 'GT Road', 'University Road'];
        $areas = ['Block A', 'Block B', 'Sector 1', 'Sector 2', 'Phase 1', 'Phase 2'];
        return rand(1, 999) . ' ' . $streets[array_rand($streets)] . ', ' . $areas[array_rand($areas)];
    }

    private function getRandomEducation()
    {
        return ['primary', 'middle', 'matric', 'intermediate', 'bachelor', 'master'][rand(0, 5)];
    }

    private function getProvinceForCity($city)
    {
        $cityToProvince = [
            'Lahore' => 'Punjab',
            'Karachi' => 'Sindh',
            'Islamabad' => 'Islamabad Capital Territory',
            'Peshawar' => 'Khyber Pakhtunkhwa',
            'Rawalpindi' => 'Punjab',
            'Faisalabad' => 'Punjab',
            'Multan' => 'Punjab',
            'Quetta' => 'Balochistan',
        ];

        return $cityToProvince[$city] ?? 'Punjab';
    }

    /**
     * Clear existing data (not used by default - call manually if needed).
     * NOTE: This method uses truncate which ignores foreign key constraints.
     */
    private function clearExistingData()
    {
        $this->command->warn('Clearing existing test data...');

        // Clear in reverse order of dependencies
        DocumentArchive::truncate();
        Remittance::truncate();
        Correspondence::truncate();
        Complaint::truncate();
        Departure::truncate();
        VisaProcess::truncate();  // Fixed: was VisaProcessing
        Undertaking::truncate();
        NextOfKin::truncate();
        RegistrationDocument::truncate();
        CandidateScreening::truncate();
        // CandidateTraining::truncate();  // Model doesn't exist
        Candidate::truncate();
        Batch::truncate();
        Trade::truncate();
        Oep::truncate();
        Campus::truncate();
        User::where('email', '!=', 'admin@example.com')->delete(); // Keep original admin if exists

        $this->command->info('Existing data cleared');
    }
}
