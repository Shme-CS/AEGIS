<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'student_id' => User::factory(),
            'title' => fake()->unique()->sentence(3),
            'slug' => fake()->unique()->slug(3),
            'abstract' => fake()->optional()->paragraph(),
            'project_type' => 'technical',
            'academic_year' => '2026/2027',
            'status' => 'draft',
        ];
    }
}
