<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(3, true),
            'subject' => fake()->randomElement(['Mathematics', 'Physics', 'Chemistry', 'Biology']),
        ];
    }
}
