<script setup lang="ts">
const props = defineProps<{ data: Record<string, unknown> }>();
const emit = defineEmits<{ (e: 'change', data: Record<string, unknown>): void }>();

function onInput(event: Event) {
    emit('change', { ...props.data, text: (event.target as HTMLInputElement).value });
}
</script>

<template>
    <div>
        <label class="mb-1 block text-xs font-medium text-muted-foreground">Texto del encabezado</label>
        <input
            :value="(data.text as string) ?? ''"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
            placeholder="Sígueme en…"
            @input="onInput"
        />
    </div>
</template>
