<?php

namespace App\Http\Controllers\Board;

use App\Http\Controllers\Controller;
use App\Http\Requests\Board\UpdateSubTaskRequest;
use App\Models\SubTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SubTaskController extends Controller
{
    public function update(UpdateSubTaskRequest $request, SubTask $subTask): RedirectResponse
    {
        Gate::authorize('update', $subTask);

        $subTask->update(['finished' => $request->boolean('finished')]);

        return back();
    }
}
