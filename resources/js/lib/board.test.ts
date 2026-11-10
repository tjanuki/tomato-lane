import { describe, expect, it } from 'vite-plus/test';
import {
    copyTask,
    dayKey,
    doneGroups,
    edgeOrder,
    moveTaskRight,
    placeTask,
    removeTask,
    setSubTaskFinished,
} from '@/lib/board';
import type { Column, ColumnKind, Task } from '@/types/board';

const VANCOUVER = 'America/Vancouver';

function task(id: number, completedAt: string | null = null): Task {
    return { id, name: `Task ${id}`, color: 'blue', completedAt, subTasks: [] };
}

function column(id: number, kind: ColumnKind, tasks: Task[]): Column {
    return { id, name: `Column ${id}`, kind, wipLimit: null, tasks };
}

function ids(board: Column[], columnId: number): number[] {
    return board.find((c) => c.id === columnId)!.tasks.map((t) => t.id);
}

describe('placeTask', () => {
    it('moves a card between columns in the dropped order', () => {
        const board = [
            column(1, 'list', [task(10), task(11)]),
            column(2, 'list', [task(20), task(21)]),
        ];

        const next = placeTask(board, 10, 2, [20, 10, 21], new Date());

        expect(ids(next, 1)).toEqual([11]);
        expect(ids(next, 2)).toEqual([20, 10, 21]);
        expect(board[0].tasks).toHaveLength(2); // input untouched
    });

    it('stamps a card entering Done and clears one leaving it', () => {
        const now = new Date('2026-11-09T18:00:00Z');
        const board = [
            column(1, 'list', [task(10)]),
            column(2, 'done', [task(20, '2026-11-01T10:00:00Z')]),
        ];

        const entered = placeTask(board, 10, 2, [10, 20], now);
        expect(entered[1].tasks[0].completedAt).toBe(now.toISOString());

        const left = placeTask(board, 20, 1, [10, 20], now);
        expect(left[0].tasks[1].completedAt).toBeNull();
    });

    it('keeps the completion time when reordering inside Done', () => {
        const stamp = '2026-11-01T10:00:00Z';
        const board = [column(2, 'done', [task(20, stamp), task(21, stamp)])];

        const next = placeTask(board, 21, 2, [21, 20], new Date());

        expect(ids(next, 2)).toEqual([21, 20]);
        expect(next[0].tasks[0].completedAt).toBe(stamp);
    });
});

describe('moveTaskRight', () => {
    it('appends to the end of the next ordinary column', () => {
        const board = [
            column(1, 'list', [task(10)]),
            column(2, 'list', [task(20)]),
        ];

        expect(ids(moveTaskRight(board, 10, new Date(), VANCOUVER), 2)).toEqual(
            [20, 10],
        );
    });

    it('files into Done below the cards completed today in the user timezone', () => {
        // 06:00 UTC on 10 Nov is 22:00 on 9 Nov in Vancouver.
        const now = new Date('2026-11-10T06:00:00Z');
        const board = [
            column(1, 'list', [task(10)]),
            column(2, 'done', [
                task(20, '2026-11-09T18:00:00Z'), // today in Vancouver
                task(21, '2026-11-08T20:00:00Z'), // yesterday
            ]),
        ];

        const next = moveTaskRight(board, 10, now, VANCOUVER);

        expect(ids(next, 2)).toEqual([20, 10, 21]);
        expect(next[1].tasks[1].completedAt).toBe(now.toISOString());
    });

    it('does nothing from the last column', () => {
        const board = [column(1, 'list', [task(10)])];

        expect(moveTaskRight(board, 10, new Date(), VANCOUVER)).toBe(board);
    });
});

describe('small transforms', () => {
    it('pins a card to the top or bottom', () => {
        const col = column(1, 'list', [task(1), task(2), task(3)]);

        expect(edgeOrder(col, 2, 'top')).toEqual([2, 1, 3]);
        expect(edgeOrder(col, 2, 'bottom')).toEqual([1, 3, 2]);
    });

    it('copies a card below the original with unticked subtasks', () => {
        const original: Task = {
            ...task(1),
            subTasks: [{ id: 5, name: 'Step', finished: true }],
        };
        const board = [column(1, 'list', [original, task(2)])];

        const next = copyTask(board, 1, -100);

        expect(ids(next, 1)).toEqual([1, -100, 2]);
        expect(next[0].tasks[1].subTasks[0].finished).toBe(false);
        expect(next[0].tasks[1].subTasks[0].id).not.toBe(5);
    });

    it('removes a card and ticks a subtask', () => {
        const withSub: Task = {
            ...task(1),
            subTasks: [{ id: 5, name: 'Step', finished: false }],
        };
        const board = [column(1, 'list', [withSub, task(2)])];

        expect(ids(removeTask(board, 2), 1)).toEqual([1]);
        expect(
            setSubTaskFinished(board, 5, true)[0].tasks[0].subTasks[0].finished,
        ).toBe(true);
    });
});

describe('doneGroups', () => {
    it('groups by day in the user timezone, newest first, undated last', () => {
        const now = new Date('2026-11-10T06:00:00Z'); // 9 Nov in Vancouver
        const groups = doneGroups(
            [
                task(1, '2026-11-08T20:00:00Z'),
                task(2, null),
                task(3, '2026-11-09T18:00:00Z'),
                task(4, '2026-11-06T20:00:00Z'),
            ],
            VANCOUVER,
            now,
        );

        expect(groups.map((g) => g.label)).toEqual([
            'Today',
            'Yesterday',
            'Friday 6 November',
            'No date',
        ]);
        expect(groups[0].tasks.map((t) => t.id)).toEqual([3]);
    });

    it('converts an instant to the local calendar day', () => {
        expect(dayKey('2026-11-10T06:00:00Z', VANCOUVER)).toBe('2026-11-09');
        expect(dayKey('2026-11-10T06:00:00Z', 'UTC')).toBe('2026-11-10');
    });
});
