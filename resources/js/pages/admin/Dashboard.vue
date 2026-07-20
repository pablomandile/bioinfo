<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Settings as SettingsIcon, Users } from 'lucide-vue-next';

defineProps<{
    stats: { users: number; admins: number; pages: number; publishedPages: number; blocks: number; views: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Administración', href: '/admin' }];
</script>

<template>
    <Head title="Administración" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <h1 class="text-lg font-semibold">Administración</h1>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Usuarios</p>
                    <p class="mt-1 text-2xl font-bold">{{ stats.users }}</p>
                    <p class="text-xs text-muted-foreground">{{ stats.admins }} admin(s)</p>
                </div>
                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Páginas</p>
                    <p class="mt-1 text-2xl font-bold">{{ stats.pages }}</p>
                    <p class="text-xs text-muted-foreground">{{ stats.publishedPages }} publicadas</p>
                </div>
                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Bloques</p>
                    <p class="mt-1 text-2xl font-bold">{{ stats.blocks }}</p>
                </div>
                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Vistas totales</p>
                    <p class="mt-1 text-2xl font-bold">{{ stats.views }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <Link href="/admin/users" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm transition hover:bg-accent">
                    <Users class="h-4 w-4" /> Gestionar usuarios
                </Link>
                <Link href="/admin/settings" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm transition hover:bg-accent">
                    <SettingsIcon class="h-4 w-4" /> Configuración
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
