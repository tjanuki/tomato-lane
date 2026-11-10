<?php

namespace App\Http\Controllers\Board;

use App\Board\TaskPlacement;
use App\Http\Controllers\Controller;
use App\Http\Requests\Board\StoreTaskRequest;
use App\Models\Column;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function store(StoreTaskRequest $request, TaskPlacement $placement): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $column = Column::query()->findOrFail($request->integer('column_id'));

        $placement->create($column, [
            'name' => trim($request->string('name')->toString()),
            'color' => $request->input('color') ?? BoardController::defaultColor($user)->value,
        ]);

        return back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return back();
    }
}
