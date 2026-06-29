<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * ProductionSeeder - Data-ready launch baseline
 *
 * Seeds ONLY what a live deployment needs before real candidate data is
 * loaded:
 *   1. Admin-tier accounts (Super Admin, Admin, Project Director)
 *   2. Essential system reference / lookup data (idempotent)
 *
 * It deliberately does NOT create:
 *   - Sample / dummy candidates
 *   - Placeholder campuses, trades, OEPs, visa partners or batches
 *   - Demo role users (campus admins, trainers, OEP users, etc.)
 *
 * Real campuses/trades/OEPs/users are expected to be created through the
 * admin UI or data import after go-live.
 *
 * SECURITY:
 *   - Admin passwords come from SEED_ADMIN_PASSWORD when set, otherwise a
 *     strong random password is generated per account.
 *   - All seeded admins have force_password_change = true.
 *   - Passwords are written ONLY to storage/logs/seeder-credentials.log
 *     (0600, git-ignored). Delete that file after distributing credentials.
 *   - Existing accounts are never modified (passwords are not reset).
 *
 * Usage:
 *   php artisan db:seed --class=ProductionSeeder
 */
class ProductionSeeder extends Seeder
{
    /** Reference / lookup seeders that are safe (idempotent) for production. */
    private const REFERENCE_SEEDERS = [
        CountriesSeeder::class,
        PaymentMethodsSeeder::class,
        ProgramsSeeder::class,
        ImplementingPartnersSeeder::class,
        CoursesSeeder::class,
        DocumentChecklistsSeeder::class,
        ComplaintTemplatesSeeder::class,
    ];

    private array $credentials = [];

    private string $credentialsLogPath;

    public function __construct()
    {
        $this->credentialsLogPath = storage_path('logs/seeder-credentials.log');
    }

    public function run(): void
    {
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('🚀 PRODUCTION BASELINE SEED (admins + system reference data)');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // 1. Admin-tier accounts only
        $this->createAdminUser('superadmin@theleap.org', 'Super Administrator', User::ROLE_SUPER_ADMIN);
        $this->createAdminUser('admin@theleap.org', 'Administrator', User::ROLE_ADMIN);
        $this->createAdminUser('director@theleap.org', 'Project Director', User::ROLE_PROJECT_DIRECTOR);

        // 2. Essential system reference / lookup data (all idempotent)
        $this->call(self::REFERENCE_SEEDERS);

        $this->finalizeCredentialsLog();

        $this->command->info('');
        $this->command->info('✅ Production baseline complete. No sample candidates were created.');
        if (! empty($this->credentials)) {
            $this->command->warn('🔐 New admin credentials written to: '.$this->credentialsLogPath);
            $this->command->warn('   Distribute securely, then DELETE that file.');
        }
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }

    /**
     * Create an admin user without ever resetting an existing account's password.
     */
    private function createAdminUser(string $email, string $name, string $role): void
    {
        if (User::where('email', $email)->exists()) {
            $this->command->line("• {$email} already exists — left unchanged.");

            return;
        }

        $password = env('SEED_ADMIN_PASSWORD') ?: $this->generateSecurePassword();

        User::create([
            'email' => $email,
            'name' => $name,
            'role' => $role,
            'password' => Hash::make($password),
            'is_active' => true,
            'force_password_change' => true,
        ]);

        $this->credentials[] = ['role' => $role, 'email' => $email, 'name' => $name];
        $this->logCredential($email, $password, $name, $role);
        $this->command->line("✓ Created {$role}: {$email}");
    }

    /**
     * Generate a strong random password meeting the government password policy
     * (uppercase, lowercase, number and special character, >= 12 chars).
     */
    private function generateSecurePassword(): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnpqrstuvwxyz';
        $digits = '23456789';
        $special = '@#$%&*!';

        $password = $upper[random_int(0, strlen($upper) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];

        $all = $upper.$lower.$digits.$special;
        for ($i = 0; $i < 13; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }

    private function logCredential(string $email, string $password, string $name, string $role): void
    {
        if (! is_dir(dirname($this->credentialsLogPath))) {
            mkdir(dirname($this->credentialsLogPath), 0700, true);
        }

        if (! file_exists($this->credentialsLogPath)) {
            $header = "WASL/TheLeap - SEEDED ADMIN CREDENTIALS (ProductionSeeder)\n"
                .'Generated: '.now()->format('Y-m-d H:i:s')."\n"
                ."⚠️  Delete this file after distributing credentials.\n"
                .str_repeat('━', 60)."\n\n";
            file_put_contents($this->credentialsLogPath, $header);
            @chmod($this->credentialsLogPath, 0600);
        }

        $entry = sprintf("[%s]\n  Name: %s\n  Email: %s\n  Password: %s\n\n", $role, $name, $email, $password);
        file_put_contents($this->credentialsLogPath, $entry, FILE_APPEND | LOCK_EX);
    }

    private function finalizeCredentialsLog(): void
    {
        Log::info('ProductionSeeder completed', [
            'new_admin_accounts' => count($this->credentials),
            'seeded_at' => now()->toISOString(),
        ]);
    }
}
