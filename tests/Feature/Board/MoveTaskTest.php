<?php

use App\Models\Task;
use Illuminate\Support\Carbon;

it('moves a task to another column and renumbers the drop order', function () {
    $user = userWithBoard();
    $monday = boardColumn($user, 'Monday');
    $a = Task::factory()->in($monday, 0)->create();
    $b = Task::factory()->in($monday, 1)->create();
    $moved = Task::factory()->in(boardColumn($user, 'Today'))->create();

    $this->actingAs($user)
        ->put(route('tasks.move', $moved), ['column_id' => $monday->id, 'ordered_ids' => [$a->id, $moved->id, $b->id]])
        ->assertRedirect();

    expect($moved->fresh()->column_id)->toBe($monday->id)
        ->and($a->fresh()->position)->toBe(0)
        ->and($moved->fresh()->position)->toBe(1)
        ->and($b->fresh()->position)->toBe(2);
});

it('date-stamps a card dragged into Done while keeping its dropped position', function () {
    $user = userWithBoard();
    $done = boardColumn($user, 'Done');
    $a = Task::factory()->in($done, 0)->create(['completed_at' => now()]);
    $b = Task::factory()->in($done, 1)->create(['completed_at' => now()]);
    $dragged = Task::factory()->in(boardColumn($user, 'Today'))->create();

    $this->actingAs($user)
        ->put(route('tasks.move', $dragged), ['column_id' => $done->id, 'ordered_ids' => [$a->id, $dragged->id, $b->id]]);

    $dragged->refresh();
    expect($dragged->column_id)->toBe($done->id)
        ->and($dragged->completed_at)->not->toBeNull()
        ->and($dragged->position)->toBe(1)
        ->and($a->fresh()->position)->toBe(0)
        ->and($b->fresh()->position)->toBe(2);
});

it('reorders within Done without changing the completion time', function () {
    $user = userWithBoard();
    $done = boardColumn($user, 'Done');
    $stamp = Carbon::parse('2026-11-01 17:00:00', 'UTC');
    $a = Task::factory()->in($done, 0)->create(['completed_at' => $stamp]);
    $b = Task::factory()->in($done, 1)->create(['completed_at' => $stamp]);

    $this->actingAs($user)
        ->put(route('tasks.move', $b), ['column_id' => $done->id, 'ordered_ids' => [$b->id, $a->id]]);

    expect($b->fresh()->position)->toBe(0)
        ->and($a->fresh()->position)->toBe(1)
        ->and($b->fresh()->completed_at?->equalTo($stamp))->toBeTrue();
});

it('clears the completion time when a card is dragged out of Done', function () {
    $user = userWithBoard();
    $ideas = boardColumn($user, 'Ideas');
    $existing = Task::factory()->in($ideas, 0)->create();
    $mover = Task::factory()->in(boardColumn($user, 'Done'))->create(['completed_at' => now()]);

    $this->actingAs($user)
        ->put(route('tasks.move', $mover), ['column_id' => $ideas->id, 'ordered_ids' => [$existing->id, $mover->id]]);

    $mover->refresh();
    expect($mover->column_id)->toBe($ideas->id)
        ->and($mover->position)->toBe(1)
        ->and($mover->completed_at)->toBeNull();
});

it('rejects an order that leaves out the moved task', function () {
    $user = userWithBoard();
    $today = boardColumn($user, 'Today');
    $other = Task::factory()->in($today)->create();
    $moved = Task::factory()->in(boardColumn($user, 'Ideas'))->create();

    $this->actingAs($user)
        ->put(route('tasks.move', $moved), ['column_id' => $today->id, 'ordered_ids' => [$other->id]])
        ->assertSessionHasErrors('ordered_ids');

    expect($moved->fresh()->column_id)->toBe(boardColumn($user, 'Ideas')->id);
});

it('rejects an order that contains another user\'s task', function () {
    $user = userWithBoard();
    $today = boardColumn($user, 'Today');
    $mine = Task::factory()->in($today)->create();
    $theirs = Task::factory()->in(boardColumn(userWithBoard(), 'Today'), 3)->create();

    $this->actingAs($user)
        ->put(route('tasks.move', $mine), ['column_id' => $today->id, 'ordered_ids' => [$theirs->id, $mine->id]])
        ->assertSessionHasErrors('ordered_ids');

    expect($theirs->fresh()->position)->toBe(3);
});

it('forbids moving another user\'s task', function () {
    $user = userWithBoard();
    $theirs = Task::factory()->in(boardColumn(userWithBoard(), 'Today'))->create();

    $this->actingAs($user)
        ->put(route('tasks.move', $theirs), ['column_id' => boardColumn($user, 'Today')->id, 'ordered_ids' => [$theirs->id]])
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('tasks.move-right', $theirs))
        ->assertForbidden();
});
