<?php

namespace Database\Factories;

use App\Enums\TaskColor;
use App\Models\Column;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'column_id' => Column::factory(),
            // A task always belongs to the owner of its column.
            'user_id' => fn (array $attributes) => Column::query()->whereKey($attributes['column_id'])->value('user_id'),
            'name' => fake()->sentence(3),
            'description' => null,
            'color' => TaskColor::Blue,
            'position' => 0,
            'completed_at' => null,
        ];
    }

    /** Place the task in the given column at the given position. */
    public function in(Column $column, int $position = 0): static
    {
        return $this->state(fn () => [
            'column_id' => $column->id,
            'user_id' => $column->user_id,
            'position' => $position,
        ]);
    }
}
