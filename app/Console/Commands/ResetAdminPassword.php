<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetAdminPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:reset-password';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset admin user password and ensure proper setup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Resetting admin password...');

        // Find or create admin user
        $admin = User::where('email', 'admin@btevta.gov.pk')->first();

        if (!$admin) {
            $this->warn('Admin user not found. Creating new admin user...');

            $admin = User::create([
                'name' => 'System Administrator',
                'email' => 'admin@btevta.gov.pk',
                'password' => Hash::make('Admin@123'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $this->info('✓ Admin user created successfully!');
        } else {
            $this->info('Admin user found. Updating password...');

            $admin->password = Hash::make('Admin@123');
            $admin->is_active = true;
            $admin->email_verified_at = now();
            $admin->save();

            $this->info('✓ Admin password updated successfully!');
        }

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📋 ADMIN CREDENTIALS:');
        $this->info('   Email: admin@btevta.gov.pk');
        $this->info('   Password: Admin@123');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Test the password
        if (Hash::check('Admin@123', $admin->password)) {
            $this->info('✅ Password verification: SUCCESS');
            $this->info('You can now login with the above credentials.');
        } else {
            $this->error('❌ Password verification: FAILED');
            $this->error('There may be an issue with password hashing.');
        }

        return 0;
    }
}
