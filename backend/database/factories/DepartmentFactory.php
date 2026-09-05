<?php

namespace Database\Factories;

use App\Models\College;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'college_id' => College::factory(),
            'name' => fake()->unique()->jobTitle() . ' Department',
            'code' => strtoupper(fake()->unique()->bothify('DEP###')),
            'description' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }
}