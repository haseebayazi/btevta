<?php

namespace Database\Seeders;

use App\Models\DocumentChecklist;
use Illuminate\Database\Seeder;

class DocumentChecklistsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documents = [
            // Mandatory Documents
            [
                'name' => 'CNIC Front & Back',
                'code' => 'cnic',
                'description' => 'Computerized National Identity Card (both sides)',
                'category' => 'mandatory',
                'is_mandatory' => true,
                'supports_multiple_pages' => true,
                'max_pages' => 5,
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Passport 1st & 2nd Page',
                'code' => 'passport',
                'description' => 'Passport first and second page with photo',
                'category' => 'mandatory',
                'is_mandatory' => true,
                'supports_multiple_pages' => true,
                'max_pages' => 5,
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Domicile',
                'code' => 'domicile',
                'description' => 'Domicile certificate',
                'category' => 'mandatory',
                'is_mandatory' => true,
                'supports_multiple_pages' => false,
                'max_pages' => 1,
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Family Registration Certificate (FRC)',
                'code' => 'frc',
                'description' => 'Family Registration Certificate',
                'category' => 'mandatory',
                'is_mandatory' => true,
                'supports_multiple_pages' => true,
                'max_pages' => 5,
                'display_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Police Character Certificate (PCC)',
                'code' => 'pcc',
                'description' => 'Police Character Certificate',
                'category' => 'mandatory',
                'is_mandatory' => true,
                'supports_multiple_pages' => false,
                'max_pages' => 1,
                'display_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Driving License',
                'code' => 'driving_license',
                'description' => 'Valid driving license (if applicable)',
                'category' => 'optional',
                'is_mandatory' => false,
                'supports_multiple_pages' => true,
                'max_pages' => 5,
                'display_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Professional License (RN, etc.)',
                'code' => 'professional_license',
                'description' => 'Professional certification or license',
                'category' => 'optional',
                'is_mandatory' => false,
                'supports_multiple_pages' => false,
                'max_pages' => 1,
                'display_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Other Mandatory Documents',
                'code' => 'other_mandatory',
                'description' => 'Any other mandatory documents',
                'category' => 'optional',
                'is_mandatory' => false,
                'supports_multiple_pages' => true,
                'max_pages' => 5,
                'display_order' => 99,
                'is_active' => true,
            ],

            // Optional Documents
            [
                'name' => 'Pre-Medical Reports',
                'code' => 'pre_medical',
                'description' => 'Medical examination reports',
                'category' => 'optional',
                'is_mandatory' => false,
                'supports_multiple_pages' => true,
                'max_pages' => 5,
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Other Certifications',
                'code' => 'certifications',
                'description' => 'Additional professional certifications',
                'category' => 'optional',
                'is_mandatory' => false,
                'supports_multiple_pages' => true,
                'max_pages' => 5,
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Candidate Resume/CV',
                'code' => 'resume',
                'description' => 'Curriculum vitae or resume',
                'category' => 'optional',
                'is_mandatory' => false,
                'supports_multiple_pages' => false,
                'max_pages' => 1,
                'display_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($documents as $document) {
            DocumentChecklist::updateOrCreate(
                ['code' => $document['code']],
                $document
            );
        }

        $this->command->info('Document checklists seeded successfully.');
    }
}
