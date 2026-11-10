<script setup lang="ts">
import { Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type { Column, Task, Tint } from '@/types/board';

const props = defineProps<{
    task: Task | null;
    column: Column | null;
    tint: Tint | null;
    timezone: string;
}>();

const emit = defineEmits<{
    close: [];
    toggleSubTask: [subTaskId: number, finished: boolean];
    delete: [task: Task];
}>();

const completed = computed(() =>
    props.task?.completedAt
        ? new Intl.DateTimeFormat('en-GB', {
              timeZone: props.timezone,
              dateStyle: 'full',
              timeStyle: 'short',
          }).format(new Date(props.task.completedAt))
        : null,
);
</script>

<template>
    <Sheet
        :open="task !== null"
        @update:open="(open: boolean) => !open && emit('close')"
    >
        <SheetContent class="gap-0 p-0 sm:max-w-md">
            <template v-if="task && tint">
                <div class="h-1.5" :style="{ backgroundColor: tint.dot }" />
                <SheetHeader class="p-5 pb-3">
                    <SheetTitle class="pr-6 text-lg leading-snug">
                        {{ task.name }}
                    </SheetTitle>
                    <SheetDescription
                        class="flex flex-wrap items-center gap-x-3 gap-y-1"
                    >
                        <span class="flex items-center gap-1.5">
                            <span
                                class="size-2.5 rounded-full"
                                :style="{ backgroundColor: tint.dot }"
                            />
                            {{ tint.project ?? task.color }}
                        </span>
                        <span v-if="column">in {{ column.name }}</span>
                    </SheetDescription>
                    <p v-if="completed" class="text-sm text-muted-foreground">
                        Completed {{ completed }}
                    </p>
                </SheetHeader>

                <div class="flex-1 overflow-y-auto px-5 pb-5">
                    <h3 class="mb-2 text-sm font-semibold">Subtasks</h3>
                    <ul v-if="task.subTasks.length > 0" class="space-y-1.5">
                        <li v-for="subTask in task.subTasks" :key="subTask.id">
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    class="size-4 rounded"
                                    :style="{ accentColor: tint.dot }"
                                    :checked="subTask.finished"
                                    :disabled="subTask.id < 0"
                                    @change="
                                        emit(
                                            'toggleSubTask',
                                            subTask.id,
                                            ($event.target as HTMLInputElement)
                                                .checked,
                                        )
                                    "
                                />
                                <span
                                    :class="{
                                        'text-muted-foreground line-through':
                                            subTask.finished,
                                    }"
                                >
                                    {{ subTask.name }}
                                </span>
                            </label>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        No subtasks yet.
                    </p>
                </div>

                <div class="border-t p-4">
                    <Button
                        variant="ghost"
                        class="text-destructive hover:text-destructive"
                        @click="emit('delete', task)"
                    >
                        <Trash2 /> Delete card
                    </Button>
                </div>
            </template>
        </SheetContent>
    </Sheet>
</template>
