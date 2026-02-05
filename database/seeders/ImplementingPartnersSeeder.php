<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\ImplementingPartner;
use Illuminate\Database\Seeder;

class ImplementingPartnersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Saudi Arabia for country linking
        $saudiArabia = Country::where('code', 'SAU')->first();
        $uae = Country::where('code', 'ARE')->first();
        $pakistan = Country::where('code', 'PAK')->first();

        $partners = [
            [
                'name' => 'Saudi Skills Development Corporation',
                'code' => 'SSDC',
                'contact_person' => 'Ahmed Al-Rashid',
                'contact_email' => 'contact@ssdc.sa',
                'contact_phone' => '+966-11-1234567',
                'address' => 'King Fahd Road, Riyadh',
                'city' => 'Riyadh',
                'country_id' => $saudiArabia?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Emirates Workforce Solutions',
                'code' => 'EWS',
                'contact_person' => 'Mohammed Al-Maktoum',
                'contact_email' => 'info@ews.ae',
                'contact_phone' => '+971-4-9876543',
                'address' => 'Business Bay, Dubai',
                'city' => 'Dubai',
                'country_id' => $uae?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Pakistan Overseas Employment Corporation',
                'code' => 'POEC',
                'contact_person' => 'Tariq Mahmood',
                'contact_email' => 'poec@mofa.gov.pk',
                'contact_phone' => '+92-51-9214678',
                'address' => 'Islamabad',
                'city' => 'Islamabad',
                'country_id' => $pakistan?->id,
                'is_active' => true,
            ],
            [
                'name' => 'BTEVTA Training Partner',
                'code' => 'BTEVTA-TP',
                'contact_person' => 'Shahid Hussain',
                'contact_email' => 'training@btevta.gov.pk',
                'contact_phone' => '+92-42-9999567',
                'address' => 'Lahore',
                'city' => 'Lahore',
                'country_id' => $pakistan?->id,
                'is_active' => true,
            ],
            [
                'name' => 'TheLeap International',
                'code' => 'TLEAP',
                'contact_person' => 'Ali Hassan',
                'contact_email' => 'info@theleap.pk',
                'contact_phone' => '+92-42-1234567',
                'address' => 'Gulberg, Lahore',
                'city' => 'Lahore',
                'country_id' => $pakistan?->id,
                'is_active' => true,
            ],
        ];

        foreach ($partners as $partner) {
            ImplementingPartner::updateOrCreate(
                ['code' => $partner['code']],
                $partner
            );
        }

        $this->command->info('Implementing Partners seeded successfully: ' . count($partners) . ' partners created.');
    }
}
