<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Student> */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'class_id' => Classroom::factory(),
            'nis' => (string) $this->faker->unique()->numerify('2025###'),
            'name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['L', 'P']),
            'parent_phone' => '62812'.$this->faker->numerify('#######'),
            'discipline_points' => 100,
            'is_active' => true,
        ];
    }
}
