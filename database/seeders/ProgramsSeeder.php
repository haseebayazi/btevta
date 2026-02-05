<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Saudi Arabia for country linking
        $saudiArabia = Country::where('code', 'SAU')->first();
        $uae = Country::where('code', 'ARE')->first();
        $qatar = Country::where('code', 'QAT')->first();
        $malaysia = Country::where('code', 'MYS')->first();

        $programs = [
            [
                'name' => 'KSA Workforce Program',
                'code' => 'KSAWP',
                'description' => 'Comprehensive workforce development program for Saudi Arabia placement',
                'duration_weeks' => 12,
                'country_id' => $saudiArabia?->id,
                'is_active' => true,
            ],
            [
                'name' => 'UAE Skilled Workers Program',
                'code' => 'UAESW',
                'description' => 'Skilled worker placement program for United Arab Emirates',
                'duration_weeks' => 8,
                'country_id' => $uae?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Qatar Construction Program',
                'code' => 'QATCON',
                'description' => 'Construction industry workforce program for Qatar',
                'duration_weeks' => 10,
                'country_id' => $qatar?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Malaysia Hospitality Program',
                'code' => 'MYHOSP',
                'description' => 'Hospitality and tourism workforce program for Malaysia',
                'duration_weeks' => 6,
                'country_id' => $malaysia?->id,
                'is_active' => true,
            ],
            [
                'name' => 'General Overseas Employment',
                'code' => 'GENOE',
                'description' => 'General overseas employment preparation program',
                'duration_weeks' => 8,
                'country_id' => null,
                'is_active' => true,
            ],
        ];

        foreach ($programs as $program) {
            Program::updateOrCreate(
                ['code' => $program['code']],
                $program
            );
        }

        $this->command->info('Programs seeded successfully: ' . count($programs) . ' programs created.');
    }
}
