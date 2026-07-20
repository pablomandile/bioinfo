<script setup lang="ts">
import type { PublicBlock } from '@/types/bio';
import { computed } from 'vue';

const props = defineProps<{ block: PublicBlock }>();

const text = computed(() => String(props.block.data.text ?? ''));
</script>

<template>
    <h2 class="pt-2 text-center text-lg font-semibold" :style="{ color: 'var(--bio-fg)' }">
        {{ text }}
    </h2>
</template>
