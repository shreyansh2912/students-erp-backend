<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionPaperFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'title' => fake()->sentence(4),
            'subject' => fake()->randomElement(['Mathematics', 'Physics', 'Chemistry', 'Biology', 'English']),
            'total_marks' => fake()->numberBetween(50, 100),
            'created_by' => User::factory(),
        ];
    }
}
