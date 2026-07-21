<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const appName = computed(() => (usePage().props.name as string | undefined) ?? 'Bioinfo');
const year = new Date().getFullYear();
</script>

<template>
    <footer class="mt-auto border-t px-4 py-3 text-center text-xs text-muted-foreground">
        © {{ year }} {{ appName }} · Desarrollado por
        <a href="https://pablomandile.com.ar" target="_blank" rel="noopener noreferrer" class="font-medium hover:underline">Pablo Mandile</a>
    </footer>
</template>
