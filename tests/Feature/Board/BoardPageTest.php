<?php

use App\Enums\TaskColor;
use App\Models\SubTask;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects guests to the login page', function () {
    $this->get(route('board'))->assertRedirect(route('login'));
});

it('shows the columns in order with their tasks and subtasks', function () {
    $user = userWithBoard();
    $today = boardColumn($user, 'Today');
    $second = Task::factory()->in($today, 1)->create(['name' => 'Second']);
    $first = Task::factory()->in($today, 0)->create(['name' => 'First', 'color' => TaskColor::Red]);
    SubTask::factory()->for($first)->create(['name' => 'Step', 'finished' => true]);

    $this->actingAs($user)
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Board')
            ->has('board', 10)
            ->where('board.0.name', 'Templates')
            ->where('board.1.name', 'Today')
            ->where('board.1.wipLimit', 10)
            ->where('board.2.kind', 'done')
            ->where('board.4.kind', 'day')
            ->has('board.1.tasks', 2)
            ->where('board.1.tasks.0.id', $first->id)
            ->where('board.1.tasks.0.color', 'red')
            ->where('board.1.tasks.0.subTasks.0.name', 'Step')
            ->where('board.1.tasks.0.subTasks.0.finished', true)
            ->where('board.1.tasks.1.id', $second->id)
            ->where('timezone', 'America/Vancouver'));
});

it('only shows the signed-in user\'s board', function () {
    $user = userWithBoard();
    $other = userWithBoard();
    Task::factory()->in(boardColumn($other, 'Today'))->create();

    $this->actingAs($user)
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('board.1.id', boardColumn($user, 'Today')->id)
            ->has('board.1.tasks', 0));
});

it('names palette colours after the user\'s projects and defaults to the first by name', function () {
    $user = userWithBoard();
    $user->projects()->createMany([
        ['color' => TaskColor::Green, 'name' => 'Health'],
        ['color' => TaskColor::Blue, 'name' => 'Admin'],
    ]);

    $this->actingAs($user)
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('palette.green.project', 'Health')
            ->where('palette.blue.project', 'Admin')
            ->where('palette.red.project', null)
            ->where('palette.red.bg', '#fbcaca')
            ->where('defaultColor', 'blue'));
});

it('creates the default board when a user registers', function () {
    $this->post(route('register.store'), [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'new@example.com')->firstOrFail();

    expect($user->columns()->pluck('name')->all())->toBe([
        'Templates', 'Today', 'Done', 'Ideas',
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',
    ]);
});
