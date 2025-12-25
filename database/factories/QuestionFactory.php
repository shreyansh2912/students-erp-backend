<?php

namespace Database\Factories;

use App\Models\QuestionPaper;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'paper_id' => QuestionPaper::factory(),
            'question_text' => fake()->sentence() . '?',
            'question_type' => fake()->randomElement(['mcq', 'short_answer']),
            'marks' => fake()->randomElement([5, 10, 15, 20]),
        ];
    }
}
