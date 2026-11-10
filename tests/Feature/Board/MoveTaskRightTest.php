<?php

use App\Models\Task;
use Illuminate\Support\Carbon;

it('moves a task to the end of the next column', function () {
    $user = userWithBoard();
    $ideas = boardColumn($user, 'Ideas');
    Task::factory()->in($ideas, 2)->create();
    $task = Task::factory()->in(boardColumn($user, 'Done'))->create(['completed_at' => now()]);

    $this->actingAs($user)->post(route('tasks.move-right', $task))->assertRedirect();

    $task->refresh();
    expect($task->column_id)->toBe($ideas->id)
        ->and($task->position)->toBe(3)
        ->and($task->completed_at)->toBeNull();
});

it('files a task moved into Done at the bottom of today\'s group', function () {
    $user = userWithBoard();
    $done = boardColumn($user, 'Done');
    Task::factory()->in($done, 9)->create(['completed_at' => now()->subDays(3)]);
    $firstToday = Task::factory()->in($done, 5)->create(['completed_at' => now()]);
    $mover = Task::factory()->in(boardColumn($user, 'Today'))->create();

    $this->actingAs($user)->post(route('tasks.move-right', $mover));

    $mover->refresh();
    expect($mover->column_id)->toBe($done->id)
        ->and($mover->completed_at)->not->toBeNull()
        ->and($mover->position)->toBe($firstToday->position + 1);
});

it('decides "today" in the user\'s timezone, not UTC', function () {
    // 06:00 UTC on 10 Nov is still 22:00 on 9 Nov in Vancouver.
    $this->travelTo(Carbon::parse('2026-11-10 06:00:00', 'UTC'));

    $user = userWithBoard();
    $done = boardColumn($user, 'Done');
    // 10:00 on 9 Nov in Vancouver: today there, but yesterday in UTC.
    Task::factory()->in($done, 3)->create(['completed_at' => Carbon::parse('2026-11-09 18:00:00', 'UTC')]);
    // 12:00 on 8 Nov in Vancouver: yesterday either way.
    Task::factory()->in($done, 7)->create(['completed_at' => Carbon::parse('2026-11-08 20:00:00', 'UTC')]);
    $mover = Task::factory()->in(boardColumn($user, 'Today'))->create();

    $this->actingAs($user)->post(route('tasks.move-right', $mover));

    // Below today's card (3), not after the whole column (7).
    expect($mover->fresh()->position)->toBe(4);
});

it('leaves a task in the last column where it is', function () {
    $user = userWithBoard();
    $task = Task::factory()->in(boardColumn($user, 'Saturday'), 2)->create();

    $this->actingAs($user)->post(route('tasks.move-right', $task))->assertRedirect();

    expect($task->fresh()->column_id)->toBe(boardColumn($user, 'Saturday')->id)
        ->and($task->fresh()->position)->toBe(2);
});
