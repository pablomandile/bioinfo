<script setup lang="ts">
const props = defineProps<{ data: Record<string, unknown> }>();
const emit = defineEmits<{ (e: 'change'): void }>();

const providers = [
    { value: 'youtube', label: 'YouTube' },
    { value: 'spotify', label: 'Spotify' },
    { value: 'tiktok', label: 'TikTok' },
];

function parse(provider: string, url: string): { id?: string; embedType?: string } {
    try {
        const parsed = new URL(url);

        if (provider === 'youtube') {
            if (parsed.hostname.includes('youtu.be')) {
                return { id: parsed.pathname.slice(1) };
            }
            if (parsed.pathname.startsWith('/embed/')) {
                return { id: parsed.pathname.split('/embed/')[1] };
            }
            if (parsed.pathname.startsWith('/shorts/')) {
                return { id: parsed.pathname.split('/shorts/')[1] };
            }
            const v = parsed.searchParams.get('v');
            if (v) {
                return { id: v };
            }
        }

        if (provider === 'spotify') {
            const parts = parsed.pathname.split('/').filter(Boolean);
            if (parts.length >= 2) {
                return { id: parts[1], embedType: parts[0] };
            }
        }
    } catch {
        // no es una URL válida todavía
    }

    return {};
}

function onProvider(event: Event) {
    props.data.provider = (event.target as HTMLSelectElement).value;
    emit('change');
}

function onUrl(event: Event) {
    const url = (event.target as HTMLInputElement).value;
    props.data.url = url;

    const provider = String(props.data.provider ?? 'youtube');
    const parsed = parse(provider, url);
    props.data.id = parsed.id ?? '';
    if (parsed.embedType) {
        props.data.embedType = parsed.embedType;
    }

    emit('change');
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
