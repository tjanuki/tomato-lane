<?php

namespace App\Http\Controllers\Board;

use App\Board\TaskPlacement;
use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MoveTaskRightController extends Controller
{
    public function __invoke(Task $task, TaskPlacement $placement): RedirectResponse
    {
        Gate::authorize('update', $task);

        $placement->moveRight($task);

        return back();
    }
}
