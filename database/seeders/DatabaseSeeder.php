<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Batch;
use App\Models\Oep;
use App\Models\VisaPartner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🔐 CREATING USER ROLES (Per ICLMS Specification)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // ============================================================
        // 1. SUPER ADMIN - Highest privilege level
        // ============================================================
        User::updateOrCreate(
            ['email' => 'superadmin@btevta.gov.pk'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('SuperAdmin@123'),
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ]
        );
        echo "✓ Super Admin created (superadmin@btevta.gov.pk / SuperAdmin@123)\n";

        // Legacy admin alias
        User::updateOrCreate(
            ['email' => 'admin@btevta.gov.pk'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('Admin@123'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );
        echo "✓ Admin created (admin@btevta.gov.pk / Admin@123)\n";

        // ============================================================
        // 2. PROJECT DIRECTOR - Oversight of all operations
        // ============================================================
        User::updateOrCreate(
            ['email' => 'director@btevta.gov.pk'],
            [
                'name' => 'Project Director',
                'password' => Hash::make('Director@123'),
                'role' => User::ROLE_PROJECT_DIRECTOR,
                'is_active' => true,
            ]
        );
        echo "✓ Project Director created (director@btevta.gov.pk / Director@123)\n";

        // ============================================================
        // 3. CAMPUSES - Required before Campus Admins
        // ============================================================
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

        // ============================================================
        // 4. CAMPUS ADMINS - Manage individual campuses
        // ============================================================
        $campusList = Campus::all();
        foreach ($campusList as $index => $campus) {
            User::updateOrCreate(
                ['email' => 'admin-' . strtolower(str_replace(' ', '', $campus->code)) . '@btevta.gov.pk'],
                [
                    'name' => $campus->name . ' Admin',
                    'password' => Hash::make('CampusAdmin@123'),
                    'role' => User::ROLE_CAMPUS_ADMIN,
                    'campus_id' => $campus->id,
                    'is_active' => true,
                ]
            );
        }

        echo "✓ Created 5 campus admin users (password: CampusAdmin@123)\n";

        // ============================================================
        // 5. TRAINERS - Conduct training sessions
        // ============================================================
        foreach ($campusList->take(3) as $campus) {
            User::updateOrCreate(
                ['email' => 'trainer-' . strtolower(str_replace(' ', '', $campus->code)) . '@btevta.gov.pk'],
                [
                    'name' => $campus->name . ' Trainer',
                    'password' => Hash::make('Trainer@123'),
                    'role' => User::ROLE_TRAINER,
                    'campus_id' => $campus->id,
                    'is_active' => true,
                ]
            );
        }

        echo "✓ Created 3 trainer users (password: Trainer@123)\n";

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

        // ============================================================
        // 6. OEP USERS - Overseas Employment Promoters
        // ============================================================
        $oepList = Oep::all();
        foreach ($oepList as $oep) {
            User::updateOrCreate(
                ['email' => 'oep-' . strtolower(str_replace(' ', '', $oep->name)) . '@btevta.gov.pk'],
                [
                    'name' => $oep->name . ' User',
                    'password' => Hash::make('OEP@123'),
                    'role' => User::ROLE_OEP,
                    'oep_id' => $oep->id,
                    'is_active' => true,
                ]
            );
        }

        echo "✓ Created 5 OEP users (password: OEP@123)\n";

        // ============================================================
        // 7. VISA PARTNERS - Handle visa processing
        // ============================================================
        $visaPartners = [
            ['name' => 'Saudi Visa Services', 'company_name' => 'SVS Ltd', 'country' => 'Saudi Arabia'],
            ['name' => 'UAE Immigration Partners', 'company_name' => 'UIP Ltd', 'country' => 'UAE'],
            ['name' => 'Qatar Visa Solutions', 'company_name' => 'QVS Ltd', 'country' => 'Qatar'],
        ];

        foreach ($visaPartners as $partner) {
            $visaPartner = VisaPartner::updateOrCreate(
                ['name' => $partner['name']],
                [
                    'company_name' => $partner['company_name'],
                    'registration_number' => 'VP-' . rand(10000, 99999),
                    'country' => $partner['country'],
                    'address' => 'Office Address',
                    'phone' => '051-9201596',
                    'email' => strtolower(str_replace(' ', '', $partner['name'])) . '@visa.com',
                    'is_active' => true,
                ]
            );

            // Create a user for each visa partner
            User::updateOrCreate(
                ['email' => 'visa-' . strtolower(str_replace(' ', '', $partner['name'])) . '@btevta.gov.pk'],
                [
                    'name' => $partner['name'] . ' User',
                    'password' => Hash::make('VisaPartner@123'),
                    'role' => User::ROLE_VISA_PARTNER,
                    'visa_partner_id' => $visaPartner->id,
                    'is_active' => true,
                ]
            );
        }

        echo "✓ Created 3 Visa Partners and users (password: VisaPartner@123)\n";

        // ============================================================
        // 8. VIEWER - Read-only access for reports
        // ============================================================
        User::updateOrCreate(
            ['email' => 'viewer@btevta.gov.pk'],
            [
                'name' => 'Report Viewer',
                'password' => Hash::make('Viewer@123'),
                'role' => User::ROLE_VIEWER,
                'is_active' => true,
            ]
        );

        echo "✓ Viewer created (viewer@btevta.gov.pk / Viewer@123)\n";

        // ============================================================
        // 9. STAFF - General staff access
        // ============================================================
        User::updateOrCreate(
            ['email' => 'staff@btevta.gov.pk'],
            [
                'name' => 'Staff Member',
                'password' => Hash::make('Staff@123'),
                'role' => User::ROLE_STAFF,
                'campus_id' => $campusList->first()->id,
                'is_active' => true,
            ]
        );

        echo "✓ Staff created (staff@btevta.gov.pk / Staff@123)\n";

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
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📋 USER CREDENTIALS BY ROLE (Per ICLMS Specification):\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        echo "1. SUPER ADMIN (Full system access):\n";
        echo "   Email: superadmin@btevta.gov.pk\n";
        echo "   Password: SuperAdmin@123\n\n";

        echo "2. ADMIN (Legacy super admin alias):\n";
        echo "   Email: admin@btevta.gov.pk\n";
        echo "   Password: Admin@123\n\n";

        echo "3. PROJECT DIRECTOR (Oversight & reports):\n";
        echo "   Email: director@btevta.gov.pk\n";
        echo "   Password: Director@123\n\n";

        echo "4. CAMPUS ADMIN (5 users - per campus):\n";
        echo "   Email: admin-camp-XXX@btevta.gov.pk\n";
        echo "   Password: CampusAdmin@123\n\n";

        echo "5. TRAINER (3 users - per campus):\n";
        echo "   Email: trainer-camp-XXX@btevta.gov.pk\n";
        echo "   Password: Trainer@123\n\n";

        echo "6. OEP (5 users - per OEP organization):\n";
        echo "   Email: oep-XXX@btevta.gov.pk\n";
        echo "   Password: OEP@123\n\n";

        echo "7. VISA PARTNER (3 users):\n";
        echo "   Email: visa-XXX@btevta.gov.pk\n";
        echo "   Password: VisaPartner@123\n\n";

        echo "8. VIEWER (Read-only reports):\n";
        echo "   Email: viewer@btevta.gov.pk\n";
        echo "   Password: Viewer@123\n\n";

        echo "9. STAFF (General access):\n";
        echo "   Email: staff@btevta.gov.pk\n";
        echo "   Password: Staff@123\n\n";

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "💡 TIP: To populate comprehensive test data for all modules, run:\n";
        echo "   php artisan db:seed --class=TestDataSeeder\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
}
