<script setup lang="ts">
import { blockRegistry } from '@/lib/blocks/registry';
import { Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const emit = defineEmits<{ (e: 'add', type: string): void }>();

const open = ref(false);
const items = computed(() => Object.values(blockRegistry).filter((def) => def.editor));

function add(type: string) {
    emit('add', type);
    open.value = false;
}
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="flex w-full items-center justify-center gap-2 rounded-lg border border-dashed py-2.5 text-sm font-medium transition hover:bg-accent"
            @click="open = !open"
        >
            <Plus class="h-4 w-4" />
            Añadir bloque
        </button>

        <div v-if="open" class="absolute z-20 mt-2 grid w-full grid-cols-2 gap-1 rounded-lg border bg-popover p-2 shadow-lg">
            <button
                v-for="def in items"
                :key="def.type"
                type="button"
                class="flex items-center gap-2 rounded-md px-3 py-2 text-left text-sm transition hover:bg-accent"
                @click="add(def.type)"
            >
                <component :is="def.icon" v-if="def.icon" class="h-4 w-4 shrink-0" />
                {{ def.label }}
            </button>
        </div>
    </div>
</template>
