<script setup lang="ts">
import type { PublicTheme } from '@/types/bio';
import { computed } from 'vue';

const props = defineProps<{ theme: PublicTheme }>();

const rootStyle = computed(() => ({
    ...props.theme.cssVars,
    background: 'var(--bio-bg, #ffffff)',
    color: 'var(--bio-fg, #111827)',
    fontFamily: 'var(--bio-font, Inter), ui-sans-serif, system-ui, sans-serif',
}));
</script>

<template>
    <div :data-mode="theme.mode" :style="rootStyle" class="min-h-screen w-full">
        <div class="mx-auto flex min-h-screen w-full max-w-lg flex-col px-5 py-10">
            <slot />
        </div>
    </div>
</template>
