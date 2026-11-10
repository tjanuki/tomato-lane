<script setup lang="ts">
import { Plus } from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import type { TaskCommand } from '@/components/board/TaskCard.vue';
import TaskCard from '@/components/board/TaskCard.vue';
import { useTaskSortable } from '@/composables/useTaskSortable';
import { doneGroups } from '@/lib/board';
import type { Column, DoneGroup, Palette } from '@/types/board';

const props = defineProps<{
    column: Column;
    palette: Palette;
    timezone: string;
    now: Date;
    selectedTaskId: number | null;
    isLast: boolean;
}>();

const emit = defineEmits<{
    drop: [taskId: number, columnId: number, orderedIds: number[]];
    create: [columnId: number, name: string];
    open: [taskId: number];
    toggleSubTask: [subTaskId: number, finished: boolean];
    command: [command: TaskCommand, taskId: number];
}>();

const list = ref<HTMLElement | null>(null);
useTaskSortable(list, (taskId, columnId, orderedIds) =>
    emit('drop', taskId, columnId, orderedIds),
);

const isDay = computed(() => props.column.kind === 'day');
const count = computed(() => props.column.tasks.length);
const overLimit = computed(
    () => props.column.wipLimit !== null && count.value > props.column.wipLimit,
);

// Done shows date headers between cards; other columns are one plain group.
const groups = computed<DoneGroup[]>(() =>
    props.column.kind === 'done'
        ? doneGroups(props.column.tasks, props.timezone, props.now)
        : [{ key: null, label: '', tasks: props.column.tasks }],
);

const tint = (color: string) => props.palette[color] ?? props.palette.white;

const adding = ref(false);
const draft = ref('');
const input = ref<HTMLInputElement | null>(null);

async function startAdding() {
    adding.value = true;
    await nextTick();
    input.value?.focus();
}

function submit() {
    const name = draft.value.trim();

    if (name !== '') {
        emit('create', props.column.id, name);
        draft.value = '';
    }
}

function stopAdding() {
    if (draft.value.trim() === '') {
        adding.value = false;
        draft.value = '';
    }
}
</script>

<template>
    <section
        class="flex w-64 shrink-0 flex-col"
        :class="
            isDay
                ? 'border-l border-border first:border-l-0'
                : 'rounded-xl bg-muted/60'
        "
    >
        <header class="flex items-center justify-between px-3 py-2">
            <span class="w-6" />
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-semibold">{{ column.name }}</h2>
                <span
                    class="rounded-full px-2 text-xs font-medium"
                    :class="
                        overLimit
                            ? 'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400'
                            : 'bg-muted-foreground/10 text-muted-foreground'
                    "
                    :title="
                        column.wipLimit
                            ? 'Cards / work-in-progress limit'
                            : 'Cards'
                    "
                >
                    {{ count
                    }}<template v-if="column.wipLimit">
                        / {{ column.wipLimit }}</template
                    >
                </span>
            </div>
            <button
                type="button"
                class="flex size-6 items-center justify-center rounded-full bg-green-500 text-white shadow-sm transition hover:bg-green-600"
                :title="`Add a card to ${column.name}`"
                @click="startAdding"
            >
                <Plus class="size-4" />
            </button>
        </header>

        <div
            ref="list"
            :data-column-id="column.id"
            class="flex min-h-16 flex-col gap-1.5 px-2 pt-1 pb-2"
        >
            <template v-for="group in groups" :key="group.key ?? 'none'">
                <div
                    v-if="group.label"
                    class="border-b border-border px-1 pt-1 pb-0.5 text-xs font-semibold text-muted-foreground"
                >
                    {{ group.label }}
                </div>
                <TaskCard
                    v-for="task in group.tasks"
                    :key="task.id"
                    :task="task"
                    :tint="tint(task.color)"
                    :selected="task.id === selectedTaskId"
                    :can-move-right="!isLast"
                    @open="emit('open', task.id)"
                    @toggle-sub-task="
                        (subTaskId, finished) =>
                            emit('toggleSubTask', subTaskId, finished)
                    "
                    @command="(command) => emit('command', command, task.id)"
                />
            </template>
        </div>

        <form v-if="adding" class="px-2 pb-3" @submit.prevent="submit">
            <input
                ref="input"
                v-model="draft"
                type="text"
                maxlength="255"
                placeholder="Card name, then Enter"
                class="w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                :aria-label="`New card in ${column.name}`"
                @keydown.esc="
                    draft = '';
                    stopAdding();
                "
                @blur="stopAdding"
            />
        </form>
    </section>
</template>
