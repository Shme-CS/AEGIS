<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;

class AegisDevelopmentSeeder extends Seeder
{
    /**
     * Seed the AEGIS development organization structure.
     */
    public function run(): void
    {
        $college = College::updateOrCreate(
            ['code' => 'COC'],
            [
                'name' => 'College of Computing',
                'description' => 'Development college for AEGIS testing.',
                'status' => 'active',
            ],
        );

        $department = Department::updateOrCreate(
            [
                'college_id' => $college->id,
                'code' => 'SWE',
            ],
            [
                'name' => 'Software Engineering',
                'description' => 'Software Engineering development department.',
                'status' => 'active',
            ],
        );

        $program = Program::updateOrCreate(
            [
                'department_id' => $department->id,
                'code' => 'BSC-SWE',
            ],
            [
                'name' => 'BSc Software Engineering',
                'description' => 'Bachelor of Science in Software Engineering.',
                'degree_type' => 'Bachelor',
                'status' => 'active',
            ],
        );

        User::where('email', 'student@aegis.test')->update([
            'program_id' => $program->id,
            'status' => 'active',
            'role' => User::ROLE_STUDENT,
        ]);
    }
}