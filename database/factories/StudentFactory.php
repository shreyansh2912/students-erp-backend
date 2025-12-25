<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'roll_number' => fake()->unique()->numerify('STU####'),
            'admission_number' => fake()->unique()->numerify('ADM######'),
            'date_of_birth' => fake()->date(),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'guardian_name' => fake()->name(),
            'guardian_phone' => fake()->phoneNumber(),
        ];
    }
}
