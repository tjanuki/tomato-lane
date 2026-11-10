<?php

namespace Database\Seeders;

use App\Board\DefaultBoard;
use App\Enums\TaskColor;
use App\Models\Column;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * A local test user (test@example.com / password) with a sample board.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        app(DefaultBoard::class)->createFor($user);

        $user->projects()->createMany([
            ['color' => TaskColor::Blue, 'name' => 'Work'],
            ['color' => TaskColor::Green, 'name' => 'Health'],
            ['color' => TaskColor::Purple, 'name' => 'Learning'],
        ]);

        /** @var array<string, Column> $columns */
        $columns = $user->columns()->get()->keyBy('name')->all();

        $cards = [
            'Templates' => [['Weekly review', TaskColor::Blue, ['Inbox zero', 'Plan next week']]],
            'Today' => [
                ['Write the board schema', TaskColor::Purple, ['Columns', 'Tasks', 'Subtasks']],
                ['Morning run', TaskColor::Green, []],
                ['Reply to emails', TaskColor::Blue, []],
            ],
            'Ideas' => [['Try Postgres full-text search', TaskColor::Purple, []]],
            'Monday' => [['Team sync', TaskColor::Blue, []]],
        ];

        foreach ($cards as $columnName => $tasks) {
            foreach ($tasks as $position => [$name, $color, $subTasks]) {
                $task = $columns[$columnName]->tasks()->make([
                    'name' => $name,
                    'color' => $color,
                    'position' => $position,
                ]);
                $task->user_id = $user->id;
                $task->save();

                foreach ($subTasks as $subTask) {
                    $task->subTasks()->create(['name' => $subTask]);
                }
            }
        }

        $done = $columns['Done'];
        foreach ([['Set up Docker Postgres', 0], ['Pick a project name', 1]] as $position => [$name, $daysAgo]) {
            $task = $done->tasks()->make([
                'name' => $name,
                'color' => TaskColor::Purple,
                'position' => $position,
                'completed_at' => now()->subDays($daysAgo),
            ]);
            $task->user_id = $user->id;
            $task->save();
        }
    }
}
