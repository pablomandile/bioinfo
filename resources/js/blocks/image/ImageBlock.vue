<script setup lang="ts">
import type { PublicBlock } from '@/types/bio';
import { computed } from 'vue';

const props = defineProps<{ block: PublicBlock }>();

const url = computed(() => String(props.block.data.url ?? ''));
const alt = computed(() => String(props.block.data.alt ?? ''));
const href = computed(() => (props.block.data.href ? String(props.block.data.href) : null));
</script>

<template>
    <component
        :is="href ? 'a' : 'div'"
        :href="href ?? undefined"
        :target="href ? '_blank' : undefined"
        :rel="href ? 'noopener noreferrer' : undefined"
        class="block w-full overflow-hidden"
        :style="{ borderRadius: 'var(--bio-btn-radius, 0.75rem)' }"
    >
        <img v-if="url" :src="url" :alt="alt" class="h-auto w-full object-cover" loading="lazy" />
    </component>
</template>
