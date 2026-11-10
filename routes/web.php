<?php

use App\Http\Controllers\Board\BoardController;
use App\Http\Controllers\Board\CopyTaskController;
use App\Http\Controllers\Board\MoveTaskController;
use App\Http\Controllers\Board\MoveTaskRightController;
use App\Http\Controllers\Board\SubTaskController;
use App\Http\Controllers\Board\TaskController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('board', [BoardController::class, 'show'])->name('board');

    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::put('tasks/{task}/move', MoveTaskController::class)->name('tasks.move');
    Route::post('tasks/{task}/move-right', MoveTaskRightController::class)->name('tasks.move-right');
    Route::post('tasks/{task}/copy', CopyTaskController::class)->name('tasks.copy');

    Route::patch('subtasks/{subTask}', [SubTaskController::class, 'update'])->name('subtasks.update');
});

require __DIR__.'/settings.php';
