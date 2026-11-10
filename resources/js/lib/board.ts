/**
 * Pure board transforms. Each returns a new board and never mutates its
 * input, so they can run inside Inertia's optimistic callbacks, which may be
 * replayed when overlapping requests settle.
 */
import type { Column, DoneGroup, Task } from '@/types/board';

type Location = { column: Column; index: number; task: Task };

export function findTask(board: Column[], taskId: number): Location | null {
    for (const column of board) {
        const index = column.tasks.findIndex((task) => task.id === taskId);

        if (index !== -1) {
            return { column, index, task: column.tasks[index] };
        }
    }

    return null;
}

/**
 * Drag and drop: put the task in a column using the dropped order. Entering
 * Done stamps the completion time; leaving Done clears it. Mirrors the
 * server's TaskPlacement::place().
 */
export function placeTask(
    board: Column[],
    taskId: number,
    columnId: number,
    orderedIds: number[],
    now: Date,
): Column[] {
    const from = findTask(board, taskId);
    const target = board.find((column) => column.id === columnId);

    if (!from || !target) {
        return board;
    }

    const wasDone = from.column.kind === 'done';
    const isDone = target.kind === 'done';
    const moved: Task = {
        ...from.task,
        completedAt:
            !wasDone && isDone
                ? now.toISOString()
                : wasDone && !isDone
                  ? null
                  : from.task.completedAt,
    };

    const byId = new Map<number, Task>();
    for (const task of target.tasks) {
        byId.set(task.id, task);
    }
    byId.set(taskId, moved);

    const reordered = orderedIds
        .map((id) => byId.get(id))
        .filter((task): task is Task => task !== undefined);

    return board.map((column) => {
        if (column.id === columnId) {
            return { ...column, tasks: reordered };
        }

        if (column.id === from.column.id) {
            return {
                ...column,
                tasks: column.tasks.filter((task) => task.id !== taskId),
            };
        }

        return column;
    });
}

/**
 * Context menu "Move right". Into Done, the card goes below the last card
 * completed today; anywhere else, to the end of the column. Mirrors
 * TaskPlacement::moveRight().
 */
export function moveTaskRight(
    board: Column[],
    taskId: number,
    now: Date,
    timezone: string,
): Column[] {
    const from = findTask(board, taskId);

    if (!from) {
        return board;
    }

    const next = board[board.indexOf(from.column) + 1];

    if (!next) {
        return board;
    }

    const isDone = next.kind === 'done';
    const moved: Task = {
        ...from.task,
        completedAt: isDone
            ? now.toISOString()
            : from.column.kind === 'done'
              ? null
              : from.task.completedAt,
    };

    const tasks = [...next.tasks];
    let insertAt = tasks.length;

    if (isDone) {
        const today = dayKey(now.toISOString(), timezone);
        const lastToday = tasks.findLastIndex(
            (task) =>
                task.completedAt !== null &&
                dayKey(task.completedAt, timezone) === today,
        );

        if (lastToday !== -1) {
            insertAt = lastToday + 1;
        }
    }

    tasks.splice(insertAt, 0, moved);

    return board.map((column) => {
        if (column.id === next.id) {
            return { ...column, tasks };
        }

        if (column.id === from.column.id) {
            return {
                ...column,
                tasks: column.tasks.filter((task) => task.id !== taskId),
            };
        }

        return column;
    });
}

/** The column order with the task pinned to the top or the bottom. */
export function edgeOrder(
    column: Column,
    taskId: number,
    edge: 'top' | 'bottom',
): number[] {
    const rest = column.tasks
        .map((task) => task.id)
        .filter((id) => id !== taskId);

    return edge === 'top' ? [taskId, ...rest] : [...rest, taskId];
}

export function appendTask(
    board: Column[],
    columnId: number,
    task: Task,
): Column[] {
    return board.map((column) =>
        column.id === columnId
            ? { ...column, tasks: [...column.tasks, task] }
            : column,
    );
}

/** A copy with unticked subtasks, directly below the original. */
export function copyTask(
    board: Column[],
    taskId: number,
    tempId: number,
): Column[] {
    const from = findTask(board, taskId);

    if (!from) {
        return board;
    }

    const copy: Task = {
        ...from.task,
        id: tempId,
        subTasks: from.task.subTasks.map((subTask, index) => ({
            ...subTask,
            id: tempId - index - 1,
            finished: false,
        })),
    };

    return board.map((column) => {
        if (column.id !== from.column.id) {
            return column;
        }

        const tasks = [...column.tasks];
        tasks.splice(from.index + 1, 0, copy);

        return { ...column, tasks };
    });
}

export function removeTask(board: Column[], taskId: number): Column[] {
    return board.map((column) => ({
        ...column,
        tasks: column.tasks.filter((task) => task.id !== taskId),
    }));
}

export function setSubTaskFinished(
    board: Column[],
    subTaskId: number,
    finished: boolean,
): Column[] {
    return board.map((column) => ({
        ...column,
        tasks: column.tasks.map((task) =>
            task.subTasks.some((subTask) => subTask.id === subTaskId)
                ? {
                      ...task,
                      subTasks: task.subTasks.map((subTask) =>
                          subTask.id === subTaskId
                              ? { ...subTask, finished }
                              : subTask,
                      ),
                  }
                : task,
        ),
    }));
}

/** The calendar day (YYYY-MM-DD) of an instant in the given timezone. */
export function dayKey(iso: string, timezone: string): string {
    // en-CA formats dates as YYYY-MM-DD.
    return new Intl.DateTimeFormat('en-CA', {
        timeZone: timezone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(new Date(iso));
}

function shiftDay(key: string, days: number): string {
    const [year, month, day] = key.split('-').map(Number);

    return new Date(Date.UTC(year, month - 1, day + days))
        .toISOString()
        .slice(0, 10);
}

function dayLabel(key: string, today: string): string {
    if (key === today) {
        return 'Today';
    }

    if (key === shiftDay(today, -1)) {
        return 'Yesterday';
    }

    const [year, month, day] = key.split('-').map(Number);

    return new Intl.DateTimeFormat('en-GB', {
        timeZone: 'UTC',
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    }).format(new Date(Date.UTC(year, month - 1, day)));
}

/**
 * Done-column cards bucketed by completion day, newest day first. Within a
 * day the column order is kept. Cards without a date come last.
 */
export function doneGroups(
    tasks: Task[],
    timezone: string,
    now: Date,
): DoneGroup[] {
    const today = dayKey(now.toISOString(), timezone);
    const groups = new Map<string | null, Task[]>();

    for (const task of tasks) {
        const key = task.completedAt
            ? dayKey(task.completedAt, timezone)
            : null;
        groups.set(key, [...(groups.get(key) ?? []), task]);
    }

    return [...groups.entries()]
        .sort(([a], [b]) => {
            if (a === null) {
                return 1;
            }

            if (b === null) {
                return -1;
            }

            return b.localeCompare(a);
        })
        .map(([key, groupTasks]) => ({
            key,
            label: key === null ? 'No date' : dayLabel(key, today),
            tasks: groupTasks,
        }));
}
