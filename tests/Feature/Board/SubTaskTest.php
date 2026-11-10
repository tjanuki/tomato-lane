<?php

use App\Models\SubTask;
use App\Models\Task;

it('ticks and unticks a subtask', function () {
    $user = userWithBoard();
    $subTask = SubTask::factory()
        ->for(Task::factory()->in(boardColumn($user, 'Today')))
        ->create(['finished' => false]);

    $this->actingAs($user)->patch(route('subtasks.update', $subTask), ['finished' => true])->assertRedirect();
    expect($subTask->fresh()->finished)->toBeTrue();

    $this->actingAs($user)->patch(route('subtasks.update', $subTask), ['finished' => false]);
    expect($subTask->fresh()->finished)->toBeFalse();
});

it('requires the finished flag', function () {
    $user = userWithBoard();
    $subTask = SubTask::factory()->for(Task::factory()->in(boardColumn($user, 'Today')))->create();

    $this->actingAs($user)
        ->patch(route('subtasks.update', $subTask), [])
        ->assertSessionHasErrors('finished');
});

it('forbids changing another user\'s subtask', function () {
    $subTask = SubTask::factory()
        ->for(Task::factory()->in(boardColumn(userWithBoard(), 'Today')))
        ->create(['finished' => false]);

    $this->actingAs(userWithBoard())
        ->patch(route('subtasks.update', $subTask), ['finished' => true])
        ->assertForbidden();

    expect($subTask->fresh()->finished)->toBeFalse();
});
