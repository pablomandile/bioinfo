<script setup lang="ts">
import { parseEmbed } from './parse';

const props = defineProps<{ data: Record<string, unknown> }>();
const emit = defineEmits<{ (e: 'change'): void }>();

const providers = [
    { value: 'youtube', label: 'YouTube' },
    { value: 'spotify', label: 'Spotify' },
    { value: 'tiktok', label: 'TikTok' },
];

/** Recalcula id/embedType a partir de la URL y el proveedor actuales. */
function resync(url: string, providerHint?: string) {
    const info = parseEmbed(url, providerHint);
    if (info.provider) {
        props.data.provider = info.provider;
    }
    props.data.id = info.id;
    if (info.embedType) {
        props.data.embedType = info.embedType;
    }
    emit('change');
}

function onProvider(event: Event) {
    const provider = (event.target as HTMLSelectElement).value;
    props.data.provider = provider;
    // Re-parsear la URL ya cargada con el proveedor recién elegido.
    resync(String(props.data.url ?? ''), provider);
}

function onUrl(event: Event) {
    const url = (event.target as HTMLInputElement).value;
    props.data.url = url;
    // Autodetecta el proveedor por el host; el desplegable es solo respaldo.
    resync(url, String(props.data.provider ?? '') || undefined);
}
</script>

<template>
    <div class="space-y-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Proveedor</label>
            <select
                :value="(data.provider as string) ?? 'youtube'"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                @change="onProvider"
            >
                <option v-for="provider in providers" :key="provider.value" :value="provider.value">
                    {{ provider.label }}
                </option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">URL del contenido</label>
            <input
                :value="(data.url as string) ?? ''"
                type="url"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                placeholder="https://youtu.be/…"
                @input="onUrl"
            />
            <p class="mt-1 text-xs text-muted-foreground">Pegá el enlace de YouTube, Spotify o TikTok.</p>
        </div>
    </div>
</template>
