<script setup lang="ts">
const props = defineProps<{ data: Record<string, unknown> }>();
const emit = defineEmits<{ (e: 'change', data: Record<string, unknown>): void }>();

function onInput(event: Event) {
    emit('change', { ...props.data, text: (event.target as HTMLTextAreaElement).value });
}
</script>

<template>
    <div>
        <label class="mb-1 block text-xs font-medium text-muted-foreground">Texto</label>
        <textarea
            :value="(data.text as string) ?? ''"
            rows="4"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
            placeholder="Escribe algo…"
            @input="onInput"
        />
    </div>
</template>
