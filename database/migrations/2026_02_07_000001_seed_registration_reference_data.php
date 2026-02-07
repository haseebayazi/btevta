<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Data migration to ensure Module 3 (Registration) reference data exists.
 *
 * This seeds the lookup tables required for the registration allocation form:
 * Countries, Payment Methods, Programs, Implementing Partners, and Courses.
 *
 * Uses updateOrCreate in each seeder, so it's safe to run multiple times.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CountriesSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\PaymentMethodsSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\ProgramsSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\ImplementingPartnersSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CoursesSeeder', '--force' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reference data is not removed on rollback to prevent data loss.
    }
};
