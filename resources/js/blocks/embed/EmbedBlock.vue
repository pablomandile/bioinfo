<script setup lang="ts">
import type { PublicBlock } from '@/types/bio';
import { computed } from 'vue';
import { parseEmbed } from './parse';

const props = defineProps<{ block: PublicBlock }>();

const fallbackUrl = computed(() => String(props.block.data.url ?? ''));

// Usa los datos ya resueltos (provider/id/embedType); si falta el id, lo deriva
// de la URL guardada. Así el embed se ve aunque solo se haya persistido la URL.
const info = computed(() => {
    const provider = String(props.block.data.provider ?? '');
    const id = String(props.block.data.id ?? '');

    if (id) {
        const embedType = props.block.data.embedType ? String(props.block.data.embedType) : undefined;
        return { provider, id, embedType };
    }

    return fallbackUrl.value ? parseEmbed(fallbackUrl.value, provider || undefined) : { provider, id: '' };
});

const iframeSrc = computed<string | null>(() => {
    if (info.value.provider === 'youtube' && info.value.id) {
        return `https://www.youtube-nocookie.com/embed/${info.value.id}`;
    }

    if (info.value.provider === 'spotify' && info.value.id) {
        const type = info.value.embedType ?? 'track';
        return `https://open.spotify.com/embed/${type}/${info.value.id}`;
    }

    return null;
});

const isSpotify = computed(() => info.value.provider === 'spotify');
</script>

<template>
    <div class="w-full overflow-hidden" :style="{ borderRadius: 'var(--bio-btn-radius, 0.75rem)' }">
        <div
            v-if="iframeSrc"
            class="relative w-full"
            :style="isSpotify ? { height: '352px' } : { aspectRatio: '16 / 9' }"
        >
            <iframe
                :src="iframeSrc"
                class="absolute inset-0 h-full w-full"
                style="border: 0"
                loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin"
                allow="accelerometer; encrypted-media; picture-in-picture; clipboard-write"
                allowfullscreen
            />
        </div>

        <a
            v-else-if="fallbackUrl"
            :href="fallbackUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="block w-full border px-5 py-3 text-center font-medium"
            :style="{
                background: 'var(--bio-btn-bg)',
                color: 'var(--bio-btn-fg)',
                borderColor: 'var(--bio-btn-border, transparent)',
                borderRadius: 'var(--bio-btn-radius, 0.75rem)',
            }"
        >
            Ver contenido
        </a>
    </div>
</template>
