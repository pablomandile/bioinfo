<script setup lang="ts">
import type { PublicBlock } from '@/types/bio';
import draggable from 'vuedraggable';
import BlockRow from './BlockRow.vue';

const props = defineProps<{ blocks: PublicBlock[]; layout: 'list' | 'grid' }>();
const emit = defineEmits<{
    (e: 'reorder'): void;
    (e: 'update', block: PublicBlock): void;
    (e: 'delete', block: PublicBlock): void;
}>();
</script>

<template>
    <draggable
        v-if="blocks.length"
        :list="props.blocks"
        item-key="id"
        handle=".drag-handle"
        :animation="150"
        class="space-y-2"
        @end="emit('reorder')"
    >
        <template #item="{ element }">
            <BlockRow :block="element" :layout="layout" @update="emit('update', element)" @delete="emit('delete', element)" />
        </template>
    </draggable>

    <p v-else class="rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground">
        Todavía no hay bloques. Añadí el primero. 👆
    </p>
</template>
