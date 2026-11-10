<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import BoardColumn from '@/components/board/BoardColumn.vue';
import type { TaskCommand } from '@/components/board/TaskCard.vue';
import TaskDetailSheet from '@/components/board/TaskDetailSheet.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useBoardActions } from '@/composables/useBoardActions';
import { findTask } from '@/lib/board';
import { board as boardRoute } from '@/routes';
import type { BoardPageProps, Task } from '@/types/board';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Board', href: boardRoute() }],
    },
});

const props = defineProps<BoardPageProps>();
const actions = useBoardActions();

// Re-evaluated every minute so the Done column's "Today" rolls over at midnight.
const now = ref(new Date());
let clock: ReturnType<typeof setInterval> | undefined;
onMounted(() => {
    clock = setInterval(() => (now.value = new Date()), 60_000);
});
onBeforeUnmount(() => clearInterval(clock));

// The detail panel reads straight from the props, so it opens instantly and
// follows the card through moves; it closes by itself if the card is deleted.
const selectedTaskId = ref<number | null>(null);
const selected = computed(() =>
    selectedTaskId.value === null
        ? null
        : findTask(props.board, selectedTaskId.value),
);

const pendingDelete = ref<Task | null>(null);

function onCommand(command: TaskCommand, taskId: number) {
    switch (command) {
        case 'move-right':
            actions.moveTaskRight(taskId);
            break;
        case 'top':
        case 'bottom':
            actions.moveToEdge(props.board, taskId, command);
            break;
        case 'copy':
            actions.copyTask(taskId);
            break;
        case 'delete':
            pendingDelete.value = findTask(props.board, taskId)?.task ?? null;
            break;
    }
}

function confirmDelete() {
    if (pendingDelete.value) {
        actions.deleteTask(pendingDelete.value.id);
    }

    pendingDelete.value = null;
}
</script>

<template>
    <Head title="Board" />

    <div class="flex flex-1 items-start gap-3 overflow-x-auto p-4">
        <BoardColumn
            v-for="(column, index) in board"
            :key="column.id"
            :column="column"
            :palette="palette"
            :timezone="timezone"
            :now="now"
            :selected-task-id="selectedTaskId"
            :is-last="index === board.length - 1"
            @drop="actions.moveTask"
            @create="
                (columnId, name) =>
                    actions.createTask(columnId, name, defaultColor)
            "
            @open="(taskId) => (selectedTaskId = taskId)"
            @toggle-sub-task="actions.setSubTaskFinished"
            @command="onCommand"
        />
    </div>

    <TaskDetailSheet
        :task="selected?.task ?? null"
        :column="selected?.column ?? null"
        :tint="selected ? (palette[selected.task.color] ?? null) : null"
        :timezone="timezone"
        @close="selectedTaskId = null"
        @toggle-sub-task="actions.setSubTaskFinished"
        @delete="(task) => (pendingDelete = task)"
    />

    <Dialog
        :open="pendingDelete !== null"
        @update:open="(open: boolean) => !open && (pendingDelete = null)"
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Delete this card?</DialogTitle>
                <DialogDescription>
                    "{{ pendingDelete?.name }}" and its subtasks will be
                    deleted. This cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="pendingDelete = null">
                    Cancel
                </Button>
                <Button variant="destructive" @click="confirmDelete">
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
