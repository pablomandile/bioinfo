<script setup lang="ts">
const props = defineProps<{ data: Record<string, unknown> }>();
const emit = defineEmits<{ (e: 'change', data: Record<string, unknown>): void }>();

function bind(key: string) {
    return {
        value: (props.data[key] as string) ?? '',
        onInput: (event: Event) => {
            emit('change', { ...props.data, [key]: (event.target as HTMLInputElement).value });
        },
    };
}
</script>

<template>
    <div class="space-y-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">URL de la imagen</label>
            <input
                v-bind="bind('url')"
                type="url"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                placeholder="https://…/imagen.jpg"
            />
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Texto alternativo</label>
            <input v-bind="bind('alt')" class="w-full rounded-md border bg-background px-3 py-2 text-sm" placeholder="Descripción de la imagen" />
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Enlace al hacer clic (opcional)</label>
            <input v-bind="bind('href')" type="url" class="w-full rounded-md border bg-background px-3 py-2 text-sm" placeholder="https://…" />
        </div>
    </div>
</template>
