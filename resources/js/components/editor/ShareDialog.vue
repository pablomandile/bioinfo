<script setup lang="ts">
import { Check, Copy, Download, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{ open: boolean; username: string; publicUrl: string; isPublished: boolean }>();
const emit = defineEmits<{ (e: 'close'): void }>();

const copied = ref(false);
const qrUrl = `/${props.username}/qr.svg`;

async function copy() {
    await navigator.clipboard.writeText(props.publicUrl);
    copied.value = true;
    setTimeout(() => (copied.value = false), 1500);
}
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="emit('close')">
            <div class="w-full max-w-sm rounded-xl bg-background p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold">Compartir</h2>
                    <button type="button" class="rounded p-1 text-muted-foreground hover:bg-accent" @click="emit('close')">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <template v-if="isPublished">
                    <div class="flex justify-center">
                        <img :src="qrUrl" alt="Código QR de la página" class="h-48 w-48 rounded-lg border bg-white p-2" />
                    </div>

                    <div class="mt-4 flex items-center gap-2 rounded-md border p-2">
                        <span class="min-w-0 flex-1 truncate text-sm">{{ publicUrl }}</span>
                        <button type="button" class="rounded p-1 hover:bg-accent" @click="copy">
                            <Check v-if="copied" class="h-4 w-4 text-green-500" />
                            <Copy v-else class="h-4 w-4" />
                        </button>
                    </div>

                    <a
                        :href="qrUrl"
                        download="qr.svg"
                        class="mt-3 flex items-center justify-center gap-2 rounded-md border py-2 text-sm transition hover:bg-accent"
                    >
                        <Download class="h-4 w-4" /> Descargar QR
                    </a>
                </template>

                <p v-else class="text-sm text-muted-foreground">
                    Publicá la página para generar su código QR y poder compartirla.
                </p>
            </div>
        </div>
    </Teleport>
</template>
