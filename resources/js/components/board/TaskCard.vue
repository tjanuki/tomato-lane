<script setup lang="ts">
import {
    ArrowDownToLine,
    ArrowRight,
    ArrowUpToLine,
    Copy,
    ListChecks,
    Trash2,
} from '@lucide/vue';
import {
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuPortal,
    ContextMenuRoot,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from 'reka-ui';
import { computed } from 'vue';
import type { Task, Tint } from '@/types/board';

export type TaskCommand = 'move-right' | 'top' | 'bottom' | 'copy' | 'delete';

const props = defineProps<{
    task: Task;
    tint: Tint;
    selected: boolean;
    canMoveRight: boolean;
}>();

const emit = defineEmits<{
    open: [];
    toggleSubTask: [subTaskId: number, finished: boolean];
    command: [command: TaskCommand];
}>();

const pending = computed(() => props.task.id < 0);
const doneCount = computed(
    () => props.task.subTasks.filter((subTask) => subTask.finished).length,
);

// A project named after its own colour ("Red" on a red card) adds nothing.
const projectLabel = computed(() => {
    const project = props.tint.project;

    return project && project.toLowerCase() !== props.task.color
        ? project
        : null;
});

const itemClass =
    'relative flex cursor-default items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-hidden select-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&_svg]:size-4 [&_svg]:text-muted-foreground';
</script>

<template>
    <ContextMenuRoot :modal="false">
        <ContextMenuTrigger as-child :disabled="pending">
            <div
                :data-task-id="task.id"
                :data-pending="pending || undefined"
                class="group relative cursor-grab rounded-md px-3 py-2.5 text-[13px] shadow-sm transition-shadow select-none active:cursor-grabbing"
                :class="{ 'opacity-60': pending }"
                :style="{
                    backgroundColor: tint.bg,
                    color: tint.text,
                    borderLeft: `4px solid ${tint.dot}`,
                    boxShadow: selected
                        ? `0 0 0 2px var(--background), 0 0 0 4px ${tint.dot}`
                        : undefined,
                }"
                @click="!pending && emit('open')"
            >
                <p class="leading-snug font-semibold">{{ task.name }}</p>

                <div
                    v-if="task.subTasks.length > 0 || projectLabel"
                    class="mt-1.5 flex items-center gap-3 text-[11px] opacity-70"
                >
                    <span
                        v-if="task.subTasks.length > 0"
                        class="flex items-center gap-1"
                        title="Subtasks done"
                    >
                        <ListChecks class="size-3.5" />
                        {{ doneCount }}/{{ task.subTasks.length }}
                    </span>
                    <span
                        v-if="projectLabel"
                        class="ml-auto flex min-w-0 items-center gap-1"
                        title="Project"
                    >
                        <span
                            class="size-2 shrink-0 rounded-full"
                            :style="{ backgroundColor: tint.dot }"
                        />
                        <span class="truncate">{{ projectLabel }}</span>
                    </span>
                </div>

                <ul
                    v-if="task.subTasks.length > 0"
                    class="mt-2 space-y-1 pt-2 text-[11px]"
                    :style="{ borderTop: `1px dashed ${tint.dot}66` }"
                >
                    <li
                        v-for="subTask in task.subTasks"
                        :key="subTask.id"
                        class="flex items-center gap-1.5"
                    >
                        <input
                            type="checkbox"
                            class="size-3.5 shrink-0 rounded"
                            :style="{ accentColor: tint.dot }"
                            :checked="subTask.finished"
                            :disabled="pending"
                            :aria-label="`Done: ${subTask.name}`"
                            @click.stop
                            @change="
                                emit(
                                    'toggleSubTask',
                                    subTask.id,
                                    ($event.target as HTMLInputElement).checked,
                                )
                            "
                        />
                        <span
                            class="truncate"
                            :class="{
                                'line-through opacity-50': subTask.finished,
                            }"
                        >
                            {{ subTask.name }}
                        </span>
                    </li>
                </ul>
            </div>
        </ContextMenuTrigger>

        <ContextMenuPortal>
            <ContextMenuContent
                class="z-50 min-w-48 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
            >
                <ContextMenuItem
                    :class="itemClass"
                    :disabled="!canMoveRight"
                    @select="emit('command', 'move-right')"
                >
                    <ArrowRight /> Move right
                </ContextMenuItem>
                <ContextMenuItem
                    :class="itemClass"
                    @select="emit('command', 'top')"
                >
                    <ArrowUpToLine /> Move to top
                </ContextMenuItem>
                <ContextMenuItem
                    :class="itemClass"
                    @select="emit('command', 'bottom')"
                >
                    <ArrowDownToLine /> Move to bottom
                </ContextMenuItem>
                <ContextMenuSeparator class="-mx-1 my-1 h-px bg-border" />
                <ContextMenuItem
                    :class="itemClass"
                    @select="emit('command', 'copy')"
                >
                    <Copy /> Copy here
                </ContextMenuItem>
                <ContextMenuSeparator class="-mx-1 my-1 h-px bg-border" />
                <ContextMenuItem
                    :class="[
                        itemClass,
                        'text-destructive [&_svg]:text-destructive',
                    ]"
                    @select="emit('command', 'delete')"
                >
                    <Trash2 /> Delete
                </ContextMenuItem>
            </ContextMenuContent>
        </ContextMenuPortal>
    </ContextMenuRoot>
</template>
