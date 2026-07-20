<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Pencil } from 'lucide-vue-next';

interface PageCard {
    id: number;
    title: string;
    slug: string;
    status: string;
    isPrimary: boolean;
    blocksCount: number;
    publicUrl: string;
    editUrl: string;
}

defineProps<{ pages: PageCard[]; username: string }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div>
                <h1 class="text-lg font-semibold">Mis páginas</h1>
                <p class="text-sm text-muted-foreground">bioinfo.test/{{ username }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="page in pages" :key="page.id" class="flex flex-col justify-between rounded-xl border p-4">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="truncate font-medium">{{ page.title }}</h2>
                            <span
                                class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="page.status === 'published' ? 'bg-green-500/15 text-green-600 dark:text-green-400' : 'bg-amber-500/15 text-amber-600 dark:text-amber-400'"
                            >
                                {{ page.status === 'published' ? 'Publicada' : 'Borrador' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">{{ page.blocksCount }} bloque(s)</p>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <Link
                            :href="page.editUrl"
                            class="flex flex-1 items-center justify-center gap-1.5 rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground transition hover:opacity-90"
                        >
                            <Pencil class="h-4 w-4" /> Editar
                        </Link>
                        <a :href="page.publicUrl" target="_blank" rel="noopener" class="rounded-md border p-2 transition hover:bg-accent" title="Ver página pública">
                            <ExternalLink class="h-4 w-4" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
