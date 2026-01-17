<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * AUDIT FIX: Complete rewrite to remove hardcoded password security vulnerability.
 * Now generates secure random passwords and properly protects production environments.
 */
class ResetAdminPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:reset-password
                            {--force : Force password reset without confirmation}
                            {--email=admin@theleap.org : Email of the admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset admin user password with a secure randomly generated password';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Production environment protection
        if (app()->environment('production')) {
            $this->warn('⚠️  WARNING: You are running this command in PRODUCTION environment!');

            if (!$this->option('force')) {
                if (!$this->confirm('Are you absolutely sure you want to reset the admin password in production?')) {
                    $this->info('Operation cancelled.');
                    return 1;
                }
            }
        }

        $email = $this->option('email');
        $this->info("Resetting password for: {$email}");

        // Find or create admin user
        $admin = User::where('email', $email)->first();

        // Generate secure random password (16 characters with mixed case, numbers, and symbols)
        $password = $this->generateSecurePassword();

        if (!$admin) {
            if ($email !== 'admin@theleap.org') {
                $this->error("User with email '{$email}' not found.");
                return 1;
            }

            $this->warn('Admin user not found. Creating new admin user...');

            $admin = User::create([
                'name' => 'System Administrator',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $this->info('✓ Admin user created successfully!');
        } else {
            $this->info('Admin user found. Updating password...');

            $admin->password = Hash::make($password);
            $admin->is_active = true;
            $admin->email_verified_at = now();
            $admin->save();

            $this->info('✓ Admin password updated successfully!');
        }

        // Display the new password ONCE
        $this->newLine();
        $this->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->warn('📋 NEW ADMIN CREDENTIALS (SAVE THESE NOW - WILL NOT BE SHOWN AGAIN!)');
        $this->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("   Email:    {$email}");
        $this->info("   Password: {$password}");
        $this->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $this->warn('⚠️  IMPORTANT SECURITY NOTES:');
        $this->line('   1. Copy this password NOW - it will not be displayed again');
        $this->line('   2. Change this password immediately after first login');
        $this->line('   3. Store credentials securely (not in plain text)');
        $this->line('   4. This action has been logged for audit purposes');
        $this->newLine();

        // Log the password reset for audit purposes (without the password!)
        activity()
            ->causedBy(null)
            ->performedOn($admin)
            ->withProperties([
                'email' => $email,
                'environment' => app()->environment(),
                'command' => 'admin:reset-password',
            ])
            ->log('Admin password was reset via console command');

        return 0;
    }

    /**
     * Generate a secure random password.
     *
     * Password requirements:
     * - 16 characters minimum
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one number
     * - At least one special character
     */
    private function generateSecurePassword(): string
    {
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghjkmnpqrstuvwxyz';
        $numbers = '23456789';
        $special = '!@#$%^&*';

        // Ensure at least one of each required character type
        $password = $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        // Fill the rest with random characters
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 0; $i < 12; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle the password to randomize character positions
        return str_shuffle($password);
    }
}
