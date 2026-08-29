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
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Título</label>
            <input v-bind="bind('label')" class="w-full rounded-md border bg-background px-3 py-2 text-sm" placeholder="Mi enlace" />
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">URL</label>
            <input
                v-bind="bind('url')"
                type="text"
                inputmode="url"
                autocapitalize="off"
                spellcheck="false"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                placeholder="https://… · mailto:vos@correo.com · tel:+54…"
            />
            <p class="mt-1 text-xs text-muted-foreground">
                Podés usar una web (https://…), un correo (mailto:vos@correo.com) o un teléfono (tel:+54…).
            </p>
        </div>
    </div>
</template>
