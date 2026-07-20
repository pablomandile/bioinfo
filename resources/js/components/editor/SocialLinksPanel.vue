<script setup lang="ts">
import { Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface SocialItem {
    id: number;
    platform: string;
    label: string;
    url: string;
}

interface PlatformOption {
    value: string;
    label: string;
}

const props = defineProps<{ social: SocialItem[]; platforms: PlatformOption[] }>();
const emit = defineEmits<{
    (e: 'add', payload: { platform: string; url: string }): void;
    (e: 'update', item: SocialItem): void;
    (e: 'delete', item: SocialItem): void;
}>();

const newPlatform = ref(props.platforms[0]?.value ?? 'instagram');
const newUrl = ref('');

function add() {
    if (!newUrl.value) {
        return;
    }
    emit('add', { platform: newPlatform.value, url: newUrl.value });
    newUrl.value = '';
}

function onPlatform(item: SocialItem, event: Event) {
    item.platform = (event.target as HTMLSelectElement).value;
    emit('update', item);
}

function onUrl(item: SocialItem, event: Event) {
    item.url = (event.target as HTMLInputElement).value;
    emit('update', item);
}
</script>

<template>
    <div class="space-y-3">
        <div v-for="item in social" :key="item.id" class="flex items-center gap-2">
            <select :value="item.platform" class="w-32 shrink-0 rounded-md border bg-background px-2 py-2 text-sm" @change="onPlatform(item, $event)">
                <option v-for="p in platforms" :key="p.value" :value="p.value">{{ p.label }}</option>
            </select>
            <input :value="item.url" type="url" class="min-w-0 flex-1 rounded-md border bg-background px-3 py-2 text-sm" placeholder="https://…" @input="onUrl(item, $event)" />
            <button type="button" class="shrink-0 rounded p-1.5 text-red-500 hover:bg-red-500/10" aria-label="Eliminar" @click="emit('delete', item)">
                <Trash2 class="h-4 w-4" />
            </button>
        </div>

        <div class="flex items-center gap-2 border-t pt-3">
            <select v-model="newPlatform" class="w-32 shrink-0 rounded-md border bg-background px-2 py-2 text-sm">
                <option v-for="p in platforms" :key="p.value" :value="p.value">{{ p.label }}</option>
            </select>
            <input v-model="newUrl" type="url" class="min-w-0 flex-1 rounded-md border bg-background px-3 py-2 text-sm" placeholder="https://…" @keyup.enter="add" />
            <button type="button" class="shrink-0 rounded-md border p-2 hover:bg-accent" aria-label="Añadir" @click="add">
                <Plus class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
