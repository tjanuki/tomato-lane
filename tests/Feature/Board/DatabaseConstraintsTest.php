<?php

use App\Enums\ColumnKind;
use App\Models\Column;
use App\Models\Task;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('rejects a colour outside the palette', function () {
    $task = Task::factory()->in(boardColumn(userWithBoard(), 'Today'))->create();

    DB::table('tasks')->where('id', $task->id)->update(['color' => 'teal']);
})->throws(QueryException::class, 'tasks_color_check');

it('allows only one Done column per user', function () {
    $user = userWithBoard();

    $user->columns()->create(['name' => 'Also done', 'kind' => ColumnKind::Done]);
})->throws(QueryException::class, 'columns_one_done_per_user');

it('refuses to delete a column that still holds cards', function () {
    $column = boardColumn(userWithBoard(), 'Today');
    Task::factory()->in($column)->create();

    $column->delete();
})->throws(QueryException::class, 'tasks_column_id_foreign');

it('deletes a user together with their whole board', function () {
    $user = userWithBoard();
    Task::factory()->in(boardColumn($user, 'Today'))->create();

    $user->delete();

    expect(Column::query()->count())->toBe(0)
        ->and(Task::query()->count())->toBe(0);
});
