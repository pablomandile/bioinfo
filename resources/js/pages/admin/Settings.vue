<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ settings: { siteName: string; registrationOpen: boolean } }>();

const form = useForm({
    siteName: props.settings.siteName,
    registrationOpen: props.settings.registrationOpen,
});

function submit() {
    form.patch('/admin/settings', { preserveScroll: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administración', href: '/admin' },
    { title: 'Configuración', href: '/admin/settings' },
];
</script>

<template>
    <Head title="Configuración" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-lg flex-col gap-6 p-4">
            <h1 class="text-lg font-semibold">Configuración del sitio</h1>

            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium">Nombre del sitio</label>
                    <input v-model="form.siteName" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    <p v-if="form.errors.siteName" class="mt-1 text-xs text-red-500">{{ form.errors.siteName }}</p>
                </div>

                <label class="flex items-center gap-3">
                    <input v-model="form.registrationOpen" type="checkbox" class="h-4 w-4 rounded border" />
                    <span class="text-sm">Permitir el registro público de nuevas cuentas</span>
                </label>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition hover:opacity-90 disabled:opacity-50"
                    >
                        Guardar
                    </button>
                    <span v-if="form.recentlySuccessful" class="text-sm text-green-600">Guardado ✓</span>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
