<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface UserRow {
    id: number;
    name: string;
    username: string | null;
    email: string;
    isActive: boolean;
    role: string;
    pagesCount: number;
}

defineProps<{ users: UserRow[]; roles: { value: string; label: string }[] }>();

const page = usePage();
const errorMsg = computed(() => (page.props.errors as Record<string, string>)?.user ?? null);

function setRole(user: UserRow, event: Event) {
    router.patch(`/admin/users/${user.id}`, { role: (event.target as HTMLSelectElement).value }, { preserveScroll: true });
}

function toggleActive(user: UserRow) {
    router.patch(`/admin/users/${user.id}`, { is_active: !user.isActive }, { preserveScroll: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administración', href: '/admin' },
    { title: 'Usuarios', href: '/admin/users' },
];
</script>

<template>
    <Head title="Usuarios" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">
            <h1 class="text-lg font-semibold">Usuarios</h1>

            <div v-if="errorMsg" class="rounded-md bg-red-500/10 px-3 py-2 text-sm text-red-600">{{ errorMsg }}</div>

            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-left text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-2">Usuario</th>
                            <th class="px-4 py-2">Email</th>
                            <th class="px-4 py-2">Páginas</th>
                            <th class="px-4 py-2">Rol</th>
                            <th class="px-4 py-2">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id" class="border-b last:border-0">
                            <td class="px-4 py-2">
                                <div class="font-medium">{{ user.name }}</div>
                                <div class="text-xs text-muted-foreground">@{{ user.username ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-2">{{ user.email }}</td>
                            <td class="px-4 py-2">{{ user.pagesCount }}</td>
                            <td class="px-4 py-2">
                                <select :value="user.role" class="rounded-md border bg-background px-2 py-1" @change="setRole(user, $event)">
                                    <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-2">
                                <button
                                    type="button"
                                    class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="user.isActive ? 'bg-green-500/15 text-green-600 dark:text-green-400' : 'bg-neutral-500/15 text-neutral-500'"
                                    @click="toggleActive(user)"
                                >
                                    {{ user.isActive ? 'Activo' : 'Inactivo' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
