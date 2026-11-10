<?php

namespace Database\Factories;

use App\Enums\ColumnKind;
use App\Models\Column;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Column>
 */
class ColumnFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->word(),
            'kind' => ColumnKind::List,
            'position' => 0,
            'wip_limit' => null,
        ];
    }

    public function done(): static
    {
        return $this->state(fn () => ['name' => 'Done', 'kind' => ColumnKind::Done]);
    }

    public function day(): static
    {
        return $this->state(fn () => ['kind' => ColumnKind::Day]);
    }
}
