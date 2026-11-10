<?php

use App\Enums\TaskColor;
use App\Models\Task;

it('appends a new task to the end of the column', function () {
    $user = userWithBoard();
    $today = boardColumn($user, 'Today');
    Task::factory()->in($today, 4)->create();

    $this->actingAs($user)
        ->post(route('tasks.store'), ['column_id' => $today->id, 'name' => '  New card  ', 'color' => 'red'])
        ->assertRedirect();

    $task = Task::query()->where('name', 'New card')->firstOrFail();
    expect($task->column_id)->toBe($today->id)
        ->and($task->position)->toBe(5)
        ->and($task->color)->toBe(TaskColor::Red)
        ->and($task->user_id)->toBe($user->id)
        ->and($task->completed_at)->toBeNull();
});

it('uses the default colour when none is given', function () {
    $user = userWithBoard();
    $user->projects()->create(['color' => TaskColor::Purple, 'name' => 'Learning']);

    $this->actingAs($user)
        ->post(route('tasks.store'), ['column_id' => boardColumn($user, 'Ideas')->id, 'name' => 'Idea'])
        ->assertRedirect();

    expect(Task::query()->firstOrFail()->color)->toBe(TaskColor::Purple);
});

it('stamps the completion time when created in Done', function () {
    $user = userWithBoard();

    $this->actingAs($user)
        ->post(route('tasks.store'), ['column_id' => boardColumn($user, 'Done')->id, 'name' => 'Already done']);

    expect(Task::query()->firstOrFail()->completed_at)->not->toBeNull();
});

it('validates the name and colour', function () {
    $user = userWithBoard();

    $this->actingAs($user)
        ->post(route('tasks.store'), ['column_id' => boardColumn($user, 'Today')->id, 'name' => '', 'color' => 'teal'])
        ->assertSessionHasErrors(['name', 'color']);

    expect(Task::query()->count())->toBe(0);
});

it('rejects another user\'s column', function () {
    $user = userWithBoard();
    $other = userWithBoard();

    $this->actingAs($user)
        ->post(route('tasks.store'), ['column_id' => boardColumn($other, 'Today')->id, 'name' => 'Sneaky'])
        ->assertSessionHasErrors('column_id');

    expect(Task::query()->count())->toBe(0);
});
