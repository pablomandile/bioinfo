<script setup lang="ts">
import type { PublicBlock } from '@/types/bio';
import { computed } from 'vue';

const props = defineProps<{ block: PublicBlock }>();

const provider = computed(() => String(props.block.data.provider ?? ''));
const id = computed(() => String(props.block.data.id ?? ''));
const fallbackUrl = computed(() => String(props.block.data.url ?? ''));

const iframeSrc = computed<string | null>(() => {
    if (provider.value === 'youtube' && id.value) {
        return `https://www.youtube-nocookie.com/embed/${id.value}`;
    }

    if (provider.value === 'spotify' && id.value) {
        const type = String(props.block.data.embedType ?? 'track');
        return `https://open.spotify.com/embed/${type}/${id.value}`;
    }

    return null;
});

const isSpotify = computed(() => provider.value === 'spotify');
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
