<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Database\Seeder;

class CoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $program = Program::where('code', 'KSAWP')->first();

        $courses = [
            [
                'name' => 'Basic Electrical Installation',
                'code' => 'ELEC-101',
                'description' => 'Introduction to electrical systems, safety, and basic installation techniques.',
                'duration_days' => 30,
                'training_type' => 'technical',
                'program_id' => $program?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Advanced Electrical Systems',
                'code' => 'ELEC-201',
                'description' => 'Advanced electrical circuits, industrial systems, and troubleshooting.',
                'duration_days' => 45,
                'training_type' => 'technical',
                'program_id' => $program?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Workplace Communication',
                'code' => 'SOFT-101',
                'description' => 'Professional communication, Arabic basics, and workplace etiquette.',
                'duration_days' => 14,
                'training_type' => 'soft_skills',
                'program_id' => $program?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Safety and Compliance Training',
                'code' => 'SAFE-101',
                'description' => 'Workplace safety, Saudi regulations, and compliance requirements.',
                'duration_days' => 7,
                'training_type' => 'soft_skills',
                'program_id' => $program?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Complete Electrician Program',
                'code' => 'ELEC-FULL',
                'description' => 'Comprehensive electrical training with technical and soft skills.',
                'duration_days' => 60,
                'training_type' => 'both',
                'program_id' => $program?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Plumbing Basics',
                'code' => 'PLMB-101',
                'description' => 'Introduction to plumbing systems and installation.',
                'duration_days' => 30,
                'training_type' => 'technical',
                'program_id' => $program?->id,
                'is_active' => true,
            ],
            [
                'name' => 'HVAC Technician Training',
                'code' => 'HVAC-101',
                'description' => 'Heating, ventilation, and air conditioning systems training.',
                'duration_days' => 45,
                'training_type' => 'both',
                'program_id' => $program?->id,
                'is_active' => true,
            ],
        ];

        foreach ($courses as $course) {
            Course::updateOrCreate(
                ['code' => $course['code']],
                $course
            );
        }

        $this->command->info('Courses seeded successfully: ' . count($courses) . ' courses created.');
    }
}
