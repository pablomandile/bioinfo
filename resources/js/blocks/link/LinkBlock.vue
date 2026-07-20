<script setup lang="ts">
import type { PublicBlock } from '@/types/bio';
import { computed } from 'vue';

const props = defineProps<{ block: PublicBlock }>();

const label = computed(() => String(props.block.data.label ?? 'Enlace'));
</script>

<template>
    <a
        :href="`/go/${block.id}`"
        class="block w-full border px-5 py-3 text-center font-medium transition hover:opacity-90"
        :style="{
            background: 'var(--bio-btn-bg)',
            color: 'var(--bio-btn-fg)',
            borderColor: 'var(--bio-btn-border, transparent)',
            borderRadius: 'var(--bio-btn-radius, 0.75rem)',
            boxShadow: 'var(--bio-btn-shadow, none)',
        }"
    >
        {{ label }}
    </a>
</template>
