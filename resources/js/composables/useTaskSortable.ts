import Sortable from 'sortablejs';
import type { Ref } from 'vue';
import { onBeforeUnmount, onMounted } from 'vue';

type OnDrop = (taskId: number, columnId: number, orderedIds: number[]) => void;

/**
 * Drag cards within and between columns.
 *
 * SortableJS moves DOM nodes itself, which would fight Vue's rendering. So on
 * drop we read the new order, put the node back where Vue left it, and hand
 * the order to onDrop. Vue then re-renders from the optimistic props.
 */
export function useTaskSortable(list: Ref<HTMLElement | null>, onDrop: OnDrop) {
    let sortable: Sortable | null = null;
    let restore: { parent: HTMLElement; next: Node | null } | null = null;

    const taskIds = (el: HTMLElement) =>
        Array.from(el.querySelectorAll<HTMLElement>(':scope > [data-task-id]'))
            .map((node) => Number(node.dataset.taskId))
            .filter((id) => id > 0);

    onMounted(() => {
        if (!list.value) {
            return;
        }

        sortable = Sortable.create(list.value, {
            group: 'tasks',
            draggable: '[data-task-id]',
            // Cards still waiting for the server cannot be dragged yet.
            filter: '[data-pending], input, button',
            preventOnFilter: false,
            animation: 150,
            ghostClass: 'opacity-40',
            delay: 150,
            delayOnTouchOnly: true,
            onStart(event) {
                restore = { parent: event.from, next: event.item.nextSibling };
            },
            onEnd(event) {
                const { from, to, item } = event;
                const moved = from !== to || event.oldIndex !== event.newIndex;
                const orderedIds = taskIds(to);

                if (restore) {
                    restore.parent.insertBefore(item, restore.next);
                    restore = null;
                }

                if (moved) {
                    onDrop(
                        Number(item.dataset.taskId),
                        Number(to.dataset.columnId),
                        orderedIds,
                    );
                }
            },
        });
    });

    onBeforeUnmount(() => sortable?.destroy());
}
