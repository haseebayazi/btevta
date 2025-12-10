<?php

/**
 * Emergency Admin Password Reset Script
 *
 * This script resets the admin password to fix login issues.
 * Run this file directly via browser or command line.
 *
 * USAGE:
 * - Browser: http://your-domain.com/fix-admin.php
 * - Command: php fix-admin.php
 *
 * DELETE THIS FILE AFTER USE!
 */

// Load Laravel
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         BTEVTA - Emergency Admin Password Reset           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

try {
    // Find or create admin user
    $admin = User::where('email', 'admin@btevta.gov.pk')->first();

    if (!$admin) {
        echo "⚠️  Admin user not found. Creating new admin user...\n\n";

        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@btevta.gov.pk',
            'password' => Hash::make('Admin@123'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        echo "✅ Admin user created successfully!\n";
    } else {
        echo "ℹ️  Admin user found. Resetting password...\n\n";

        $admin->password = Hash::make('Admin@123');
        $admin->is_active = true;
        $admin->email_verified_at = now();
        $admin->role = 'admin'; // Ensure role is admin
        $admin->save();

        echo "✅ Admin password reset successfully!\n";
    }

    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📋 ADMIN CREDENTIALS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "   Email: admin@btevta.gov.pk\n";
    echo "   Password: Admin@123\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\n";

    // Verify the password hash
    if (Hash::check('Admin@123', $admin->password)) {
        echo "✅ Password verification: SUCCESS\n";
        echo "✅ Hash algorithm: " . password_get_info($admin->password)['algoName'] . "\n";
        echo "\n";
        echo "🎉 You can now login with the above credentials!\n";
    } else {
        echo "❌ Password verification: FAILED\n";
        echo "⚠️  There may be a hashing algorithm issue.\n";
        echo "    Current hash: " . substr($admin->password, 0, 30) . "...\n";
    }

    echo "\n";
    echo "⚠️  IMPORTANT: Delete this file (fix-admin.php) after use!\n";
    echo "\n";

} catch (\Exception $e) {
    echo "\n";
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    echo "\n";
}

echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";
