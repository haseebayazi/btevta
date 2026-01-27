<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Candidate;

class RegistrationVerificationTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Candidate::whereNull('registration_verification_token')->chunk(100, function ($candidates) {
            foreach ($candidates as $candidate) {
                $candidate->update([
                    'registration_verification_token' => bin2hex(random_bytes(16)),
                    'registration_verification_sent_at' => now(),
                ]);
            }
        });
    }
}
