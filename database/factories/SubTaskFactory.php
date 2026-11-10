<?php

namespace Database\Factories;

use App\Models\SubTask;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubTask>
 */
class SubTaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'name' => fake()->sentence(2),
            'finished' => false,
        ];
    }
}
