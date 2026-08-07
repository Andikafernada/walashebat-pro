<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Classroom> */
class ClassroomFactory extends Factory
{
    protected $model = \App\Models\Classroom::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'XII '.$this->faker->randomElement(['RPL', 'TKJ']).' '.$this->faker->numberBetween(1, 3),
            'academic_year' => '2025/2026',
            'major' => $this->faker->randomElement(['RPL', 'TKJ']),
            'is_active' => true,
            'auto_attendance' => false,
        ];
    }
}
