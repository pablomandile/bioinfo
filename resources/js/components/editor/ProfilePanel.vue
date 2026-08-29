<script setup lang="ts">
import { Upload, X } from 'lucide-vue-next';

interface EditablePage {
    title: string | null;
    bio: string | null;
    avatarUrl: string | null;
    username: string;
}

defineProps<{ page: EditablePage }>();
const emit = defineEmits<{
    (e: 'update', patch: Partial<EditablePage>): void;
    (e: 'avatar', file: File): void;
    (e: 'avatar-remove'): void;
}>();

function onFile(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        emit('avatar', file);
    }
}

function onTitle(event: Event) {
    emit('update', { title: (event.target as HTMLInputElement).value });
}

function onBio(event: Event) {
    emit('update', { bio: (event.target as HTMLTextAreaElement).value });
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center gap-4">
            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-full border bg-muted">
                <img v-if="page.avatarUrl" :src="page.avatarUrl" alt="Avatar" class="h-full w-full object-cover" />
            </div>
            <div class="flex flex-col gap-2">
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-md border px-3 py-1.5 text-sm font-medium hover:bg-accent">
                    <Upload class="h-4 w-4" />
                    Subir avatar
                    <input type="file" accept="image/*" class="hidden" @change="onFile" />
                </label>
                <button
                    v-if="page.avatarUrl"
                    type="button"
                    class="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-red-500"
                    @click="emit('avatar-remove')"
                >
                    <X class="h-3 w-3" /> Quitar
                </button>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Título</label>
            <input
                :value="page.title ?? ''"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                placeholder="Tu nombre o marca"
                @input="onTitle"
            />
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Bio</label>
            <textarea
                :value="page.bio ?? ''"
                rows="3"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                placeholder="Contá quién sos…"
                @input="onBio"
            />
        </div>
    </div>
</template>
