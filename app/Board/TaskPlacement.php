<?php

namespace App\Board;

use App\Models\Column;
use App\Models\SubTask;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Where cards sit on the board: ordering within a column, moves between
 * columns, and the completion stamp that comes with entering Done.
 */
class TaskPlacement
{
    /**
     * Add a new card to the end of a column.
     *
     * @param  array{name: string, color: string, description?: string|null}  $attributes
     */
    public function create(Column $column, array $attributes): Task
    {
        $task = new Task([
            ...$attributes,
            'column_id' => $column->id,
            'position' => $this->nextPosition($column),
            'completed_at' => $column->isDone() ? now() : null,
        ]);
        $task->user_id = $column->user_id;
        $task->save();

        return $task;
    }

    /**
     * Drag and drop: put the task in a column, keeping the dropped order.
     *
     * $orderedIds is the full order of the target column after the drop and
     * must contain the task. Entering Done stamps the completion time;
     * leaving Done clears it.
     *
     * @param  list<int>  $orderedIds
     */
    public function place(Task $task, Column $column, array $orderedIds): void
    {
        $wasDone = $task->column->isDone();

        DB::transaction(function () use ($task, $column, $orderedIds, $wasDone) {
            $this->renumber($task->user_id, $column->id, $orderedIds);

            if (! $wasDone && $column->isDone()) {
                $task->forceFill(['completed_at' => now()])->save();
            } elseif ($wasDone && ! $column->isDone()) {
                $task->forceFill(['completed_at' => null])->save();
            }
        });
    }

    /**
     * Context menu "Move right": shift the task to the next column. Returns
     * false when the task is already in the last column.
     */
    public function moveRight(Task $task): bool
    {
        $current = $task->column;

        $next = Column::query()
            ->where('user_id', $task->user_id)
            ->whereRaw('(position, id) > (?, ?)', [$current->position, $current->id])
            ->orderBy('position')
            ->orderBy('id')
            ->first();

        if ($next === null) {
            return false;
        }

        $task->column_id = $next->id;

        if ($next->isDone()) {
            $task->completed_at = now();
            $task->position = $this->bottomOfTodayInDone($next, $task);
        } else {
            $task->position = $this->nextPosition($next);

            if ($current->isDone()) {
                $task->completed_at = null;
            }
        }

        $task->save();

        return true;
    }

    /**
     * Context menu "Copy": duplicate the task and its subtasks (unticked)
     * directly below the original.
     */
    public function copy(Task $task): Task
    {
        return DB::transaction(function () use ($task) {
            $copy = $task->replicate(['created_at', 'updated_at']);
            $copy->save();

            $task->subTasks->each(fn (SubTask $subTask) => $copy->subTasks()->create([
                'name' => $subTask->name,
                'finished' => false,
            ]));

            /** @var list<int> $ids */
            $ids = Task::query()
                ->where('column_id', $task->column_id)
                ->whereKeyNot($copy->id)
                ->orderBy('position')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $after = array_search($task->id, $ids, true);
            array_splice($ids, $after === false ? count($ids) : $after + 1, 0, [$copy->id]);

            $this->renumber($task->user_id, $task->column_id, $ids);

            return $copy->refresh();
        });
    }

    /**
     * Give the listed tasks positions 0..n-1 in one statement. unnest() WITH
     * ORDINALITY turns the id array into (id, ord) rows to join against.
     *
     * @param  list<int>  $orderedIds
     */
    private function renumber(int $userId, int $columnId, array $orderedIds): void
    {
        DB::update(
            <<<'SQL'
                UPDATE tasks
                SET column_id = ?, position = o.ord - 1, updated_at = now()
                FROM unnest(?::bigint[]) WITH ORDINALITY AS o(id, ord)
                WHERE tasks.id = o.id AND tasks.user_id = ?
                SQL,
            [$columnId, '{'.implode(',', array_map('intval', $orderedIds)).'}', $userId],
        );
    }

    private function nextPosition(Column $column): int
    {
        $max = Task::query()->where('column_id', $column->id)->max('position');

        return $max === null ? 0 : (int) $max + 1;
    }

    /**
     * Just below the last card completed today in the user's timezone, or at
     * the end of the column when nothing was completed today.
     */
    private function bottomOfTodayInDone(Column $done, Task $task): int
    {
        $timezone = (string) config('app.user_timezone');

        // Datetime bindings carry no offset, so convert the boundaries to UTC.
        $startOfToday = now($timezone)->startOfDay()->utc();
        $startOfTomorrow = now($timezone)->addDay()->startOfDay()->utc();

        $others = Task::query()->where('column_id', $done->id)->whereKeyNot($task->id);

        $max = (clone $others)
            ->where('completed_at', '>=', $startOfToday)
            ->where('completed_at', '<', $startOfTomorrow)
            ->max('position')
            ?? $others->max('position');

        return $max === null ? 0 : (int) $max + 1;
    }
}
