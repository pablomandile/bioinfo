<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    class?: string;
}

defineProps<Props>();

const appName = computed(() => (usePage().props.name as string | undefined) ?? 'Bioinfo');
</script>

<template>
    <img src="/img/logo.png" :alt="appName" class="h-auto max-h-24 w-full object-contain" />
</template>
