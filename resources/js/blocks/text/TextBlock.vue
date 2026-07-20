<script setup lang="ts">
import type { PublicBlock } from '@/types/bio';
import { computed } from 'vue';

const props = defineProps<{ block: PublicBlock }>();

const text = computed(() => String(props.block.data.text ?? ''));
</script>

<template>
    <p class="whitespace-pre-wrap text-center text-sm opacity-90" :style="{ color: 'var(--bio-fg)' }">
        {{ text }}
    </p>
</template>
