import type { VisitOptions } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import {
    appendTask,
    copyTask,
    edgeOrder,
    findTask,
    moveTaskRight,
    placeTask,
    removeTask,
    setSubTaskFinished,
} from '@/lib/board';
import tasks from '@/routes/tasks';
import subtasks from '@/routes/subtasks';
import type { Column } from '@/types/board';

type BoardProps = { board: Column[]; defaultColor: string; timezone: string };

/** Ids for optimistic cards; negative so they never clash with real ones. */
let nextTempId = -1;
const tempId = () => (nextTempId -= 1000);

/**
 * Every board action updates the page props immediately (Inertia's
 * optimistic option) and saves in the background. When the request fails,
 * Inertia restores the props and replays any other pending updates.
 */
export function useBoardActions() {
    const visit = (
        route: Parameters<typeof router.visit>[0],
        data: Record<string, unknown>,
        update: (props: BoardProps) => Column[],
    ) => {
        const options: VisitOptions = {
            data: data as VisitOptions['data'],
            preserveScroll: true,
            preserveState: true,
            only: ['board'],
            showProgress: false,
            optimistic: (props) => ({
                board: update(props as unknown as BoardProps),
            }),
            onError: () => {
                toast.error('That change was not saved.');
            },
            onNetworkError: () => {
                toast.error(
                    'Could not reach the server. The change was undone.',
                );
            },
        };

        router.visit(route, options);
    };

    const moveTask = (taskId: number, columnId: number, orderedIds: number[]) =>
        visit(
            tasks.move(taskId),
            { column_id: columnId, ordered_ids: orderedIds },
            (props) =>
                placeTask(
                    props.board,
                    taskId,
                    columnId,
                    orderedIds,
                    new Date(),
                ),
        );

    return {
        moveTask,

        createTask(columnId: number, name: string, color: string) {
            visit(
                tasks.store(),
                { column_id: columnId, name, color },
                (props) =>
                    appendTask(props.board, columnId, {
                        id: tempId(),
                        name,
                        color,
                        completedAt:
                            props.board.find((c) => c.id === columnId)?.kind ===
                            'done'
                                ? new Date().toISOString()
                                : null,
                        subTasks: [],
                    }),
            );
        },

        moveToEdge(board: Column[], taskId: number, edge: 'top' | 'bottom') {
            const location = findTask(board, taskId);

            if (location) {
                moveTask(
                    taskId,
                    location.column.id,
                    edgeOrder(location.column, taskId, edge),
                );
            }
        },

        moveTaskRight(taskId: number) {
            visit(tasks.moveRight(taskId), {}, (props) =>
                moveTaskRight(props.board, taskId, new Date(), props.timezone),
            );
        },

        copyTask(taskId: number) {
            visit(tasks.copy(taskId), {}, (props) =>
                copyTask(props.board, taskId, tempId()),
            );
        },

        deleteTask(taskId: number) {
            visit(tasks.destroy(taskId), {}, (props) =>
                removeTask(props.board, taskId),
            );
        },

        setSubTaskFinished(subTaskId: number, finished: boolean) {
            visit(subtasks.update(subTaskId), { finished }, (props) =>
                setSubTaskFinished(props.board, subTaskId, finished),
            );
        },
    };
}
