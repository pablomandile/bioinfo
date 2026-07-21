<script setup lang="ts">
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { BarChart3, ExternalLink, MoreVertical, Pencil, Plus, Star, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface PageCard {
    id: number;
    title: string;
    slug: string;
    status: string;
    isPrimary: boolean;
    blocksCount: number;
    publicUrl: string;
    publicPath: string;
    editUrl: string;
    analyticsUrl: string;
}

const props = defineProps<{ pages: PageCard[]; username: string; canCreate: boolean; maxPages: number }>();

const inertiaPage = usePage();
const errorMsg = computed(() => (inertiaPage.props.errors as Record<string, string>)?.pages ?? null);

function createBiolink() {
    router.post('/dashboard/pages', {}, { preserveScroll: true });
}

function makePrimary(card: PageCard) {
    router.patch(`/dashboard/pages/${card.id}/primary`, {}, { preserveScroll: true });
}

function deleteBiolink(card: PageCard) {
    if (confirm(`¿Eliminar el biolink "${card.title}"? Esta acción no se puede deshacer.`)) {
        router.delete(`/dashboard/pages/${card.id}`, { preserveScroll: true });
    }
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Administrar links', href: '/dashboard' }];
</script>

<template>
    <Head title="Mis Biolinks" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-lg font-semibold">Mis Biolinks</h1>
                    <p class="text-sm text-muted-foreground">{{ pages.length }} de {{ maxPages }} biolinks</p>
                </div>
                <button
                    type="button"
                    :disabled="!canCreate"
                    class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                    :title="canCreate ? '' : `Alcanzaste el máximo de ${maxPages} biolinks`"
                    @click="createBiolink"
                >
                    <Plus class="h-4 w-4" /> Agregar Biolink
                </button>
            </div>

            <div v-if="errorMsg" class="rounded-md bg-red-500/10 px-3 py-2 text-sm text-red-600">{{ errorMsg }}</div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="card in pages" :key="card.id" class="flex flex-col justify-between rounded-xl border p-4">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h2 class="truncate font-medium">{{ card.title }}</h2>
                                    <span
                                        v-if="card.isPrimary"
                                        class="shrink-0 rounded-full bg-blue-500/15 px-2 py-0.5 text-xs font-medium text-blue-600 dark:text-blue-400"
                                    >
                                        Principal
                                    </span>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-muted-foreground">bioinfo.test{{ card.publicPath }}</p>
                            </div>

                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <button type="button" class="shrink-0 rounded-md p-1.5 text-muted-foreground transition hover:bg-accent">
                                        <MoreVertical class="h-4 w-4" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem v-if="!card.isPrimary" @click="makePrimary(card)">
                                        <Star class="mr-2 h-4 w-4" /> Hacer principal
                                    </DropdownMenuItem>
                                    <DropdownMenuItem v-if="pages.length > 1" class="text-red-600" @click="deleteBiolink(card)">
                                        <Trash2 class="mr-2 h-4 w-4" /> Eliminar
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <div class="mt-2 flex items-center gap-2">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="card.status === 'published' ? 'bg-green-500/15 text-green-600 dark:text-green-400' : 'bg-amber-500/15 text-amber-600 dark:text-amber-400'"
                            >
                                {{ card.status === 'published' ? 'Publicada' : 'Borrador' }}
                            </span>
                            <span class="text-xs text-muted-foreground">{{ card.blocksCount }} bloque(s)</span>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <Link
                            :href="card.editUrl"
                            class="flex flex-1 items-center justify-center gap-1.5 rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground transition hover:opacity-90"
                        >
                            <Pencil class="h-4 w-4" /> Editar
                        </Link>
                        <Link :href="card.analyticsUrl" class="rounded-md border p-2 transition hover:bg-accent" title="Analíticas">
                            <BarChart3 class="h-4 w-4" />
                        </Link>
                        <a :href="card.publicUrl" target="_blank" rel="noopener" class="rounded-md border p-2 transition hover:bg-accent" title="Ver página pública">
                            <ExternalLink class="h-4 w-4" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
