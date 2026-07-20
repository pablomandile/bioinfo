<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Eye, MousePointerClick, Pencil, Percent } from 'lucide-vue-next';
import { computed } from 'vue';

interface SeriesPoint {
    date: string;
    views: number;
    clicks: number;
}

const props = defineProps<{
    page: { id: number; title: string; editUrl: string };
    totals: { views: number; clicks: number; ctr: number };
    series: SeriesPoint[];
    topBlocks: { label: string; clicks: number }[];
}>();

const maxViews = computed(() => Math.max(1, ...props.series.map((s) => s.views)));
const maxClicks = computed(() => Math.max(1, ...props.series.map((s) => s.clicks)));

function fmtDate(date: string): string {
    const [, month, day] = date.split('-');
    return `${day}/${month}`;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Analíticas', href: '#' },
];
</script>

<template>
    <Head title="Analíticas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold">Analíticas</h1>
                    <p class="text-sm text-muted-foreground">{{ page.title }}</p>
                </div>
                <Link :href="page.editUrl" class="inline-flex items-center gap-1.5 rounded-md border px-3 py-2 text-sm transition hover:bg-accent">
                    <Pencil class="h-4 w-4" /> Editar
                </Link>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border p-4">
                    <div class="flex items-center gap-2 text-muted-foreground"><Eye class="h-4 w-4" /><span class="text-sm">Vistas</span></div>
                    <p class="mt-2 text-3xl font-bold">{{ totals.views }}</p>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="flex items-center gap-2 text-muted-foreground"><MousePointerClick class="h-4 w-4" /><span class="text-sm">Clics</span></div>
                    <p class="mt-2 text-3xl font-bold">{{ totals.clicks }}</p>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="flex items-center gap-2 text-muted-foreground"><Percent class="h-4 w-4" /><span class="text-sm">CTR</span></div>
                    <p class="mt-2 text-3xl font-bold">{{ totals.ctr }}%</p>
                </div>
            </div>

            <div class="rounded-xl border p-4">
                <h2 class="mb-4 text-sm font-semibold">Últimos 30 días</h2>
                <div class="space-y-4">
                    <div>
                        <p class="mb-1 text-xs text-muted-foreground">Vistas</p>
                        <div class="flex h-24 items-end gap-0.5">
                            <div
                                v-for="point in series"
                                :key="point.date"
                                class="flex-1"
                                :title="`${fmtDate(point.date)}: ${point.views} vistas`"
                            >
                                <div class="w-full rounded-t bg-primary/70" :style="{ height: (point.views / maxViews) * 100 + '%' }"></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <p class="mb-1 text-xs text-muted-foreground">Clics</p>
                        <div class="flex h-24 items-end gap-0.5">
                            <div
                                v-for="point in series"
                                :key="point.date"
                                class="flex-1"
                                :title="`${fmtDate(point.date)}: ${point.clicks} clics`"
                            >
                                <div class="w-full rounded-t bg-green-500/70" :style="{ height: (point.clicks / maxClicks) * 100 + '%' }"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border p-4">
                <h2 class="mb-3 text-sm font-semibold">Enlaces más clickeados</h2>
                <div v-if="topBlocks.length" class="space-y-2">
                    <div v-for="(block, index) in topBlocks" :key="index" class="flex items-center justify-between gap-3">
                        <span class="truncate text-sm">{{ block.label }}</span>
                        <span class="shrink-0 rounded-full bg-accent px-2 py-0.5 text-xs font-medium">{{ block.clicks }}</span>
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">Todavía no hay clics registrados.</p>
            </div>
        </div>
    </AppLayout>
</template>
