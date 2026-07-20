<script setup lang="ts">
import { resolveBlock } from '@/lib/blocks/registry';
import type { PublicBlock } from '@/types/bio';
import { ChevronDown, Eye, EyeOff, GripVertical, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ block: PublicBlock; layout: 'list' | 'grid' }>();
const emit = defineEmits<{ (e: 'update'): void; (e: 'delete'): void }>();

const open = ref(false);
const def = computed(() => resolveBlock(props.block.type));

const summary = computed(() => {
    const data = props.block.data as Record<string, unknown>;
    return String(data.label ?? data.text ?? data.url ?? data.provider ?? def.value?.label ?? props.block.type);
});

function toggleVisible() {
    props.block.isVisible = !props.block.isVisible;
    emit('update');
}

function onSize(event: Event) {
    props.block.size = (event.target as HTMLSelectElement).value as PublicBlock['size'];
    emit('update');
}
</script>

<template>
    <div class="rounded-lg border bg-card">
        <div class="flex items-center gap-2 px-3 py-2.5">
            <button type="button" class="drag-handle cursor-grab text-muted-foreground active:cursor-grabbing" aria-label="Reordenar">
                <GripVertical class="h-4 w-4" />
            </button>

            <component :is="def?.icon" v-if="def?.icon" class="h-4 w-4 shrink-0 text-muted-foreground" />

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium" :class="{ 'opacity-50': !block.isVisible }">{{ summary }}</p>
                <p class="text-xs text-muted-foreground">{{ def?.label ?? block.type }}</p>
            </div>

            <button type="button" class="rounded p-1.5 text-muted-foreground hover:bg-accent" :aria-label="block.isVisible ? 'Ocultar' : 'Mostrar'" @click="toggleVisible">
                <Eye v-if="block.isVisible" class="h-4 w-4" />
                <EyeOff v-else class="h-4 w-4" />
            </button>

            <button type="button" class="rounded p-1.5 text-muted-foreground hover:bg-accent" aria-label="Editar" @click="open = !open">
                <ChevronDown class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" />
            </button>

            <button type="button" class="rounded p-1.5 text-red-500 hover:bg-red-500/10" aria-label="Eliminar" @click="emit('delete')">
                <Trash2 class="h-4 w-4" />
            </button>
        </div>

        <div v-if="open" class="space-y-3 border-t px-3 py-3">
            <component :is="def?.editor" v-if="def?.editor" :data="block.data" @change="emit('update')" />

            <div v-if="layout === 'grid'">
                <label class="mb-1 block text-xs font-medium text-muted-foreground">Tamaño en el grid</label>
                <select :value="block.size" class="w-full rounded-md border bg-background px-3 py-2 text-sm" @change="onSize">
                    <option value="sm">Pequeño</option>
                    <option value="md">Mediano</option>
                    <option value="lg">Grande (ancho completo)</option>
                </select>
            </div>
        </div>
    </div>
</template>
