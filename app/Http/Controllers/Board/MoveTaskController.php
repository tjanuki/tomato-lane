<?php

namespace App\Http\Controllers\Board;

use App\Board\TaskPlacement;
use App\Http\Controllers\Controller;
use App\Http\Requests\Board\MoveTaskRequest;
use App\Models\Column;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class MoveTaskController extends Controller
{
    /** Drag and drop. */
    public function __invoke(MoveTaskRequest $request, Task $task, TaskPlacement $placement): RedirectResponse
    {
        $placement->place(
            $task,
            Column::query()->findOrFail($request->integer('column_id')),
            $request->orderedIds(),
        );

        return back();
    }
}
