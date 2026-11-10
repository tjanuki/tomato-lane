<?php

use App\Enums\TaskColor;
use App\Models\SubTask;
use App\Models\Task;

it('copies a task and its subtasks directly below the original', function () {
    $user = userWithBoard();
    $templates = boardColumn($user, 'Templates');
    $original = Task::factory()->in($templates, 0)->create(['name' => 'Weekly review', 'color' => TaskColor::Green]);
    $after = Task::factory()->in($templates, 1)->create();
    SubTask::factory()->for($original)->create(['name' => 'Inbox zero', 'finished' => true]);
    SubTask::factory()->for($original)->create(['name' => 'Plan', 'finished' => false]);

    $this->actingAs($user)->post(route('tasks.copy', $original))->assertRedirect();

    $copy = Task::query()->whereKeyNot([$original->id, $after->id])->with('subTasks')->firstOrFail();
    expect($copy->name)->toBe('Weekly review')
        ->and($copy->color)->toBe(TaskColor::Green)
        ->and($copy->column_id)->toBe($templates->id)
        ->and($copy->subTasks->pluck('name')->all())->toBe(['Inbox zero', 'Plan'])
        ->and($copy->subTasks->pluck('finished')->unique()->all())->toBe([false])
        ->and($original->fresh()->position)->toBe(0)
        ->and($copy->position)->toBe(1)
        ->and($after->fresh()->position)->toBe(2);
});

it('forbids copying another user\'s task', function () {
    $theirs = Task::factory()->in(boardColumn(userWithBoard(), 'Today'))->create();

    $this->actingAs(userWithBoard())->post(route('tasks.copy', $theirs))->assertForbidden();

    expect(Task::query()->count())->toBe(1);
});

it('deletes a task with its subtasks', function () {
    $user = userWithBoard();
    $task = Task::factory()->in(boardColumn($user, 'Today'))->create();
    SubTask::factory()->for($task)->create();

    $this->actingAs($user)->delete(route('tasks.destroy', $task))->assertRedirect();

    expect(Task::query()->count())->toBe(0)
        ->and(SubTask::query()->count())->toBe(0);
});

it('forbids deleting another user\'s task', function () {
    $theirs = Task::factory()->in(boardColumn(userWithBoard(), 'Today'))->create();

    $this->actingAs(userWithBoard())->delete(route('tasks.destroy', $theirs))->assertForbidden();

    expect($theirs->fresh())->not->toBeNull();
});
