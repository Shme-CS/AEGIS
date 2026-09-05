<?php

namespace Database\Factories;

use App\Models\College;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<College>
 */
class CollegeFactory extends Factory
{
    protected $model = College::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company() . ' College',
            'code' => strtoupper(fake()->unique()->bothify('COL###')),
            'description' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }
}