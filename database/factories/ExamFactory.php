<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Organization;
use App\Models\QuestionPaper;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'batch_id' => Batch::factory(),
            'paper_id' => QuestionPaper::factory(),
            'title' => fake()->sentence(4),
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(2),
            'duration_minutes' => 120,
            'status' => 'draft',
        ];
    }
}
