<script setup lang="ts">
import type { PublicBlock } from '@/types/bio';
import { computed } from 'vue';

const props = defineProps<{ block: PublicBlock }>();

const label = computed(() => String(props.block.data.label ?? 'Enlace'));
const url = computed(() => String(props.block.data.url ?? ''));

// mailto: y tel: se abren directamente con el esquema nativo del navegador.
// El redirect de tracking (/go/{id}) no es fiable para esos esquemas, así que
// solo se usa para enlaces http(s) (que además son los que interesa medir).
const isDirect = computed(() => /^(mailto:|tel:)/i.test(url.value));
const href = computed(() => (isDirect.value ? url.value : `/go/${props.block.id}`));
</script>

<template>
    <a
        :href="href"
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
