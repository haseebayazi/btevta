<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            // Pakistan - Home country
            [
                'name' => 'Pakistan',
                'code' => 'PAK',
                'code_2' => 'PK',
                'currency_code' => 'PKR',
                'phone_code' => '+92',
                'is_destination' => false,
                'is_active' => true,
            ],

            // Destination Countries - Middle East
            [
                'name' => 'Saudi Arabia',
                'code' => 'SAU',
                'code_2' => 'SA',
                'currency_code' => 'SAR',
                'phone_code' => '+966',
                'is_destination' => true,
                'specific_requirements' => [
                    'iqama_required' => true,
                    'tracking_apps' => ['Absher', 'Qiwa'],
                    'trade_test' => 'Takamol',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'United Arab Emirates',
                'code' => 'ARE',
                'code_2' => 'AE',
                'currency_code' => 'AED',
                'phone_code' => '+971',
                'is_destination' => true,
                'specific_requirements' => [
                    'emirates_id_required' => true,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Qatar',
                'code' => 'QAT',
                'code_2' => 'QA',
                'currency_code' => 'QAR',
                'phone_code' => '+974',
                'is_destination' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Kuwait',
                'code' => 'KWT',
                'code_2' => 'KW',
                'currency_code' => 'KWD',
                'phone_code' => '+965',
                'is_destination' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Bahrain',
                'code' => 'BHR',
                'code_2' => 'BH',
                'currency_code' => 'BHD',
                'phone_code' => '+973',
                'is_destination' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Oman',
                'code' => 'OMN',
                'code_2' => 'OM',
                'currency_code' => 'OMR',
                'phone_code' => '+968',
                'is_destination' => true,
                'is_active' => true,
            ],

            // Other Common Destinations
            [
                'name' => 'Malaysia',
                'code' => 'MYS',
                'code_2' => 'MY',
                'currency_code' => 'MYR',
                'phone_code' => '+60',
                'is_destination' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Singapore',
                'code' => 'SGP',
                'code_2' => 'SG',
                'currency_code' => 'SGD',
                'phone_code' => '+65',
                'is_destination' => true,
                'is_active' => true,
            ],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['code' => $country['code']],
                $country
            );
        }

        $this->command->info('Countries seeded successfully.');
    }
}
