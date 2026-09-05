<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => fake()->unique()->jobTitle(),
            'code' => strtoupper(fake()->unique()->bothify('???###')),
            'description' => fake()->optional()->sentence(),
            'degree_type' => fake()->randomElement([
                'Bachelor',
                'Master',
                'Diploma',
            ]),
            'status' => 'active',
        ];
    }
}