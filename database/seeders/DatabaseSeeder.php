<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Batch;
use App\Models\Oep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        User::updateOrCreate(
            ['email' => 'admin@btevta.gov.pk'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('Admin@123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        echo "✓ Admin created (admin@btevta.gov.pk / Admin@123)\n";

        // Create Campuses
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
                    'email' => strtolower(str_replace(' ', '', $campus['code'])) . '@btevta.gov.pk',
                    'is_active' => true,
                ]
            );
        }

        echo "✓ Created 5 campuses\n";

        // Create Campus Admins
        $campusList = Campus::all();
        foreach ($campusList as $index => $campus) {
            User::updateOrCreate(
                ['email' => 'admin-' . strtolower(str_replace(' ', '', $campus->code)) . '@btevta.gov.pk'],
                [
                    'name' => $campus->name . ' Admin',
                    'password' => Hash::make('Admin@123'),
                    'role' => 'campus_admin',
                    'campus_id' => $campus->id,
                    'is_active' => true,
                ]
            );
        }

        echo "✓ Created 5 campus admin users\n";

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

        // Create OEP Users
        $oepList = Oep::all();
        foreach ($oepList as $oep) {
            User::updateOrCreate(
                ['email' => 'oep-' . strtolower(str_replace(' ', '', $oep->name)) . '@btevta.gov.pk'],
                [
                    'name' => $oep->name . ' User',
                    'password' => Hash::make('OEP@123'),
                    'role' => 'oep',
                    'oep_id' => $oep->id,
                    'is_active' => true,
                ]
            );
        }

        echo "✓ Created 5 OEP users\n";

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

        echo "\n✨ Database seeding completed successfully!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📋 DEFAULT CREDENTIALS:\n";
        echo "   Email: admin@btevta.gov.pk\n";
        echo "   Password: Admin@123\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "\n💡 TIP: To populate comprehensive test data for all modules, run:\n";
        echo "   php artisan db:seed --class=TestDataSeeder\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
}
