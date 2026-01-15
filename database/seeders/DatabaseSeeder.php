<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Batch;
use App\Models\Oep;
use App\Models\VisaPartner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * DatabaseSeeder - Secure Implementation
 *
 * SECURITY FEATURES:
 * - Random passwords generated using Str::random(16)
 * - Passwords logged to secure file (not console)
 * - All seeded users have force_password_change=true
 * - No passwords echoed to console output
 *
 * After seeding, check: storage/logs/seeder-credentials.log
 * This file should be securely deleted after initial setup.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Credential storage for secure logging
     */
    private array $credentials = [];

    /**
     * Path to secure credentials log file
     */
    private string $credentialsLogPath;

    public function __construct()
    {
        $this->credentialsLogPath = storage_path('logs/seeder-credentials.log');
    }

    public function run(): void
    {
        // Initialize credentials log
        $this->initializeCredentialsLog();

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🔐 CREATING USER ROLES (Secure Mode)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // ============================================================
        // 1. SUPER ADMIN - Highest privilege level
        // ============================================================
        $this->createSecureUser([
            'email' => 'superadmin@theleap.org',
            'name' => 'Super Administrator',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
        echo "✓ Super Admin created (superadmin@theleap.org)\n";

        // Legacy admin alias
        $this->createSecureUser([
            'email' => 'admin@theleap.org',
            'name' => 'Administrator',
            'role' => User::ROLE_ADMIN,
        ]);
        echo "✓ Admin created (admin@theleap.org)\n";

        // ============================================================
        // 2. PROJECT DIRECTOR - Oversight of all operations
        // ============================================================
        $this->createSecureUser([
            'email' => 'director@theleap.org',
            'name' => 'Project Director',
            'role' => User::ROLE_PROJECT_DIRECTOR,
        ]);
        echo "✓ Project Director created (director@theleap.org)\n";

        // ============================================================
        // 3. CAMPUSES - Required before Campus Admins
        // ============================================================
        $campuses = [
            ['name' => 'Rawalpindi Campus', 'code' => 'CAMP-RWP'],
            ['name' => 'Islamabad Campus', 'code' => 'CAMP-ISB'],
            ['name' => 'Lahore Campus', 'code' => 'CAMP-LHR'],
            ['name' => 'Karachi Campus', 'code' => 'CAMP-KHI'],
            ['name' => 'Peshawar Campus', 'code' => 'CAMP-PSH'],
        ];

        foreach ($campuses as $campus) {
            Campus::updateOrCreate(
                ['code' => $campus['code']],
                [
                    'name' => $campus['name'],
                    'address' => '123 Main Street',
                    'city' => explode(' ', $campus['name'])[0],
                    'contact_person' => 'Manager',
                    'phone' => '051-9201596',
                    'email' => strtolower(str_replace(' ', '', $campus['code'])) . '@theleap.org',
                    'is_active' => true,
                ]
            );
        }
        echo "✓ Created 5 campuses\n";

        // ============================================================
        // 4. CAMPUS ADMINS - Manage individual campuses
        // ============================================================
        $campusList = Campus::all();
        foreach ($campusList as $campus) {
            $email = 'admin-' . strtolower(str_replace(' ', '', $campus->code)) . '@theleap.org';
            $this->createSecureUser([
                'email' => $email,
                'name' => $campus->name . ' Admin',
                'role' => User::ROLE_CAMPUS_ADMIN,
                'campus_id' => $campus->id,
            ]);
        }
        echo "✓ Created 5 campus admin users\n";

        // ============================================================
        // 5. TRAINERS - Conduct training sessions
        // ============================================================
        foreach ($campusList->take(3) as $campus) {
            $email = 'trainer-' . strtolower(str_replace(' ', '', $campus->code)) . '@theleap.org';
            $this->createSecureUser([
                'email' => $email,
                'name' => $campus->name . ' Trainer',
                'role' => User::ROLE_TRAINER,
                'campus_id' => $campus->id,
            ]);
        }
        echo "✓ Created 3 trainer users\n";

        // Create Trades
        $trades = [
            ['code' => 'TRADE-ELEC', 'name' => 'Electrician', 'category' => 'Technical'],
            ['code' => 'TRADE-PLUM', 'name' => 'Plumber', 'category' => 'Service'],
            ['code' => 'TRADE-CONS', 'name' => 'Construction Worker', 'category' => 'Construction'],
            ['code' => 'TRADE-WELD', 'name' => 'Welder', 'category' => 'Technical'],
            ['code' => 'TRADE-CARP', 'name' => 'Carpenter', 'category' => 'Construction'],
        ];

        foreach ($trades as $trade) {
            Trade::updateOrCreate(
                ['code' => $trade['code']],
                [
                    'name' => $trade['name'],
                    'description' => $trade['name'] . ' training program',
                    'category' => $trade['category'],
                    'duration_months' => 12,
                    'is_active' => true,
                ]
            );
        }
        echo "✓ Created 5 trades\n";

        // Create OEPs (Overseas Employment Promoters)
        $oeps = [
            ['name' => 'Global Employment Services', 'company_name' => 'GES Ltd'],
            ['name' => 'International Manpower Solutions', 'company_name' => 'IMS Ltd'],
            ['name' => 'Arab Employment Partners', 'company_name' => 'AEP Ltd'],
            ['name' => 'Gulf Workforce Solutions', 'company_name' => 'GWS Ltd'],
            ['name' => 'Expert Manpower Ltd', 'company_name' => 'EML Ltd'],
        ];

        foreach ($oeps as $oep) {
            Oep::updateOrCreate(
                ['company_name' => $oep['company_name']],
                [
                    'name' => $oep['name'],
                    'registration_number' => 'REG-' . rand(10000, 99999),
                    'address' => 'Office Address',
                    'phone' => '051-9201596',
                    'email' => strtolower(str_replace(' ', '', $oep['name'])) . '@oep.com',
                    'website' => 'https://oep.com',
                    'contact_person' => 'Manager',
                    'is_active' => true,
                ]
            );
        }
        echo "✓ Created 5 OEPs\n";

        // ============================================================
        // 6. OEP USERS - Overseas Employment Promoters
        // ============================================================
        $oepList = Oep::all();
        foreach ($oepList as $oep) {
            $email = 'oep-' . strtolower(str_replace(' ', '', $oep->name)) . '@theleap.org';
            $this->createSecureUser([
                'email' => $email,
                'name' => $oep->name . ' User',
                'role' => User::ROLE_OEP,
                'oep_id' => $oep->id,
            ]);
        }
        echo "✓ Created 5 OEP users\n";

        // ============================================================
        // 7. VISA PARTNERS - Handle visa processing
        // ============================================================
        $visaPartners = [
            ['name' => 'Saudi Visa Services', 'company_name' => 'SVS Ltd', 'country' => 'Saudi Arabia'],
            ['name' => 'UAE Immigration Partners', 'company_name' => 'UIP Ltd', 'country' => 'UAE'],
            ['name' => 'Qatar Visa Solutions', 'company_name' => 'QVS Ltd', 'country' => 'Qatar'],
        ];

        foreach ($visaPartners as $partner) {
            $visaPartner = VisaPartner::updateOrCreate(
                ['name' => $partner['name']],
                [
                    'company_name' => $partner['company_name'],
                    'registration_number' => 'VP-' . rand(10000, 99999),
                    'country' => $partner['country'],
                    'address' => 'Office Address',
                    'phone' => '051-9201596',
                    'email' => strtolower(str_replace(' ', '', $partner['name'])) . '@visa.com',
                    'is_active' => true,
                ]
            );

            $email = 'visa-' . strtolower(str_replace(' ', '', $partner['name'])) . '@theleap.org';
            $this->createSecureUser([
                'email' => $email,
                'name' => $partner['name'] . ' User',
                'role' => User::ROLE_VISA_PARTNER,
                'visa_partner_id' => $visaPartner->id,
            ]);
        }
        echo "✓ Created 3 Visa Partners and users\n";

        // ============================================================
        // 8. VIEWER - Read-only access for reports
        // ============================================================
        $this->createSecureUser([
            'email' => 'viewer@theleap.org',
            'name' => 'Report Viewer',
            'role' => User::ROLE_VIEWER,
        ]);
        echo "✓ Viewer created (viewer@theleap.org)\n";

        // ============================================================
        // 9. STAFF - General staff access
        // ============================================================
        $this->createSecureUser([
            'email' => 'staff@theleap.org',
            'name' => 'Staff Member',
            'role' => User::ROLE_STAFF,
            'campus_id' => $campusList->first()->id,
        ]);
        echo "✓ Staff created (staff@theleap.org)\n";

        // Create Batches
        if (Batch::count() === 0) {
            $tradeList = Trade::all();
            $campusListForBatch = Campus::all();

            foreach ($tradeList as $trade) {
                for ($i = 1; $i <= 3; $i++) {
                    Batch::create([
                        'name' => $trade->name . ' Batch ' . $i,
                        'description' => 'Batch for ' . $trade->name,
                        'trade_id' => $trade->id,
                        'campus_id' => $campusListForBatch->random()->id,
                        'start_date' => now()->addDays($i * 10),
                        'end_date' => now()->addDays($i * 10 + 30),
                        'capacity' => 30,
                        'status' => 'planned',
                    ]);
                }
            }
            echo "✓ Created 15 batches\n";
        } else {
            echo "✓ Batches already exist, skipping...\n";
        }

        // Create Sample Candidates using factories
        if (\App\Models\Candidate::count() === 0) {
            echo "Creating sample candidates using factories...\n";
            \App\Models\Candidate::factory(50)->create();
            echo "✓ Created 50 sample candidates\n";
        } else {
            echo "✓ Candidates already exist, skipping...\n";
        }

        // Finalize credentials log
        $this->finalizeCredentialsLog();

        echo "\n✨ Database seeding completed successfully!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🔐 SECURITY NOTICE:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        echo "⚠️  Credentials have been saved to:\n";
        echo "    " . $this->credentialsLogPath . "\n\n";
        echo "⚠️  IMPORTANT SECURITY ACTIONS:\n";
        echo "    1. Securely distribute credentials to users\n";
        echo "    2. DELETE the credentials log file after distribution\n";
        echo "    3. All users MUST change password on first login\n\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "💡 TIP: To populate comprehensive test data for all modules, run:\n";
        echo "   php artisan db:seed --class=TestDataSeeder\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }

    /**
     * Initialize the credentials log file
     */
    private function initializeCredentialsLog(): void
    {
        $header = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $header .= "WASL/TheLeap - SEEDED USER CREDENTIALS\n";
        $header .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $header .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $header .= "⚠️  SECURITY WARNING:\n";
        $header .= "    - DELETE this file after distributing credentials\n";
        $header .= "    - All users MUST change password on first login\n";
        $header .= "    - Do NOT commit this file to version control\n\n";
        $header .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        file_put_contents($this->credentialsLogPath, $header);

        // Set restrictive permissions on the credentials file
        chmod($this->credentialsLogPath, 0600);
    }

    /**
     * Create a user with a secure random password
     *
     * @param array $userData User data including email, name, role, and optional relations
     * @return User
     */
    private function createSecureUser(array $userData): User
    {
        // Generate a secure random password
        $password = $this->generateSecurePassword();

        $user = User::updateOrCreate(
            ['email' => $userData['email']],
            [
                'name' => $userData['name'],
                'password' => Hash::make($password),
                'role' => $userData['role'],
                'campus_id' => $userData['campus_id'] ?? null,
                'oep_id' => $userData['oep_id'] ?? null,
                'visa_partner_id' => $userData['visa_partner_id'] ?? null,
                'is_active' => true,
                'force_password_change' => true, // SECURITY: Force password change on first login
            ]
        );

        // Store credential for logging
        $this->credentials[] = [
            'role' => $this->getRoleDisplayName($userData['role']),
            'email' => $userData['email'],
            'password' => $password,
            'name' => $userData['name'],
        ];

        // Log to secure file immediately
        $this->logCredential($userData['role'], $userData['email'], $password, $userData['name']);

        return $user;
    }

    /**
     * Generate a secure random password
     *
     * Format: XxxxXxxx@999 (2 uppercase, 6 lowercase, 1 special, 3 digits)
     * This ensures the password meets strong password requirements
     *
     * @return string
     */
    private function generateSecurePassword(): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '@#$%&*!';

        // Build password with guaranteed complexity
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)]; // 1 uppercase
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)]; // 2nd uppercase
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];    // 1 special
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];    // 3 numbers
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];

        return $password;
    }

    /**
     * Log a credential to the secure file
     */
    private function logCredential(string $role, string $email, string $password, string $name): void
    {
        $roleName = $this->getRoleDisplayName($role);
        $entry = sprintf(
            "[%s]\n  Name: %s\n  Email: %s\n  Password: %s\n\n",
            $roleName,
            $name,
            $email,
            $password
        );

        file_put_contents($this->credentialsLogPath, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Finalize the credentials log
     */
    private function finalizeCredentialsLog(): void
    {
        $footer = "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $footer .= "Total users created: " . count($this->credentials) . "\n";
        $footer .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $footer .= "\n⚠️  REMINDER: Delete this file after distributing credentials!\n";
        $footer .= "    Command: rm " . $this->credentialsLogPath . "\n";

        file_put_contents($this->credentialsLogPath, $footer, FILE_APPEND | LOCK_EX);

        // Log to Laravel log for audit purposes (without passwords)
        Log::info('Database seeded with secure credentials', [
            'user_count' => count($this->credentials),
            'credentials_file' => $this->credentialsLogPath,
            'seeded_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get display name for a role
     */
    private function getRoleDisplayName(string $role): string
    {
        return match ($role) {
            User::ROLE_SUPER_ADMIN => 'Super Admin',
            User::ROLE_ADMIN => 'Admin',
            User::ROLE_PROJECT_DIRECTOR => 'Project Director',
            User::ROLE_CAMPUS_ADMIN => 'Campus Admin',
            User::ROLE_TRAINER => 'Trainer',
            User::ROLE_OEP => 'OEP',
            User::ROLE_VISA_PARTNER => 'Visa Partner',
            User::ROLE_VIEWER => 'Viewer',
            User::ROLE_STAFF => 'Staff',
            default => ucfirst($role),
        };
    }
}
