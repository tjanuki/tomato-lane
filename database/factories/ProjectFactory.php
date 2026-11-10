<?php

namespace Database\Factories;

use App\Enums\TaskColor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'color' => fake()->randomElement(TaskColor::cases()),
            'name' => fake()->words(2, true),
        ];
    }
}
