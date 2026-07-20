<script setup lang="ts">
const props = defineProps<{ data: Record<string, unknown> }>();
const emit = defineEmits<{ (e: 'change'): void }>();

function onInput(event: Event) {
    props.data.text = (event.target as HTMLTextAreaElement).value;
    emit('change');
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
