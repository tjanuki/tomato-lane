<?php

namespace App\Http\Controllers\Board;

use App\Enums\TaskColor;
use App\Http\Controllers\Controller;
use App\Models\Column;
use App\Models\Project;
use App\Models\SubTask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $columns = $user->columns()->with('tasks.subTasks')->get();
        $projects = $user->projects()->get()->keyBy(fn (Project $project) => $project->color->value);

        return Inertia::render('Board', [
            'board' => $columns->map(fn (Column $column) => $this->column($column))->values(),
            'palette' => collect(TaskColor::cases())->mapWithKeys(fn (TaskColor $color) => [
                $color->value => [
                    ...$color->tint(),
                    'project' => $projects->get($color->value)?->name,
                ],
            ]),
            'defaultColor' => self::defaultColor($user)->value,
            'timezone' => config('app.user_timezone'),
        ]);
    }

    /**
     * @return array{id: int, name: string, kind: string, wipLimit: int|null, tasks: list<array{id: int, name: string, color: string, completedAt: string|null, subTasks: list<array{id: int, name: string, finished: bool}>}>}
     */
    private function column(Column $column): array
    {
        return [
            'id' => $column->id,
            'name' => $column->name,
            'kind' => $column->kind->value,
            'wipLimit' => $column->wip_limit,
            'tasks' => array_values($column->tasks->map(fn (Task $task) => $this->task($task))->all()),
        ];
    }

    /**
     * @return array{id: int, name: string, color: string, completedAt: string|null, subTasks: list<array{id: int, name: string, finished: bool}>}
     */
    private function task(Task $task): array
    {
        return [
            'id' => $task->id,
            'name' => $task->name,
            'color' => $task->color->value,
            'completedAt' => $task->completed_at?->toIso8601ZuluString(),
            'subTasks' => array_values($task->subTasks->map(fn (SubTask $subTask) => [
                'id' => $subTask->id,
                'name' => $subTask->name,
                'finished' => $subTask->finished,
            ])->all()),
        ];
    }

    /** The colour of the user's first project by name, or white. */
    public static function defaultColor(User $user): TaskColor
    {
        return $user->projects()->orderBy('name')->first()->color ?? TaskColor::White;
    }
}
