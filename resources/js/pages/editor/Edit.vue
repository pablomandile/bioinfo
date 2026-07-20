<script setup lang="ts">
import AddBlockMenu from '@/components/editor/AddBlockMenu.vue';
import BlockListEditor from '@/components/editor/BlockListEditor.vue';
import PhonePreview from '@/components/editor/PhonePreview.vue';
import ProfilePanel from '@/components/editor/ProfilePanel.vue';
import SocialLinksPanel from '@/components/editor/SocialLinksPanel.vue';
import ThemePanel from '@/components/editor/ThemePanel.vue';
import { api, debounce } from '@/composables/useApi';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PageTheme, ThemePreset } from '@/lib/theme';
import type { BreadcrumbItem } from '@/types';
import type { PublicBlock } from '@/types/bio';
import { Head } from '@inertiajs/vue3';
import { Check, Copy, ExternalLink, Loader2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

interface EditablePageData {
    id: number;
    username: string;
    slug: string;
    title: string | null;
    bio: string | null;
    avatarUrl: string | null;
    layout: 'list' | 'grid';
    status: string;
    meta_title: string | null;
    meta_description: string | null;
    theme: PageTheme | null;
    publicUrl: string;
}

interface SocialItem {
    id: number;
    platform: string;
    label: string;
    url: string;
}

const props = defineProps<{
    page: EditablePageData;
    blocks: PublicBlock[];
    social: SocialItem[];
    presets: ThemePreset[];
    blockTypes: { type: string; label: string }[];
    socialPlatforms: { value: string; label: string }[];
}>();

const page = reactive({
    ...props.page,
    theme: {
        presetId: props.page.theme?.presetId ?? null,
        mode: props.page.theme?.mode ?? 'light',
        tokens: props.page.theme?.tokens ?? {},
    } as PageTheme,
});

const blocks = reactive<PublicBlock[]>([...props.blocks]);
const social = reactive<SocialItem[]>([...props.social]);

const saving = ref(false);
const error = ref<string | null>(null);
const copied = ref(false);

const base = `/dashboard/pages/${page.id}`;

async function run<T>(promise: Promise<T>): Promise<T | undefined> {
    saving.value = true;
    error.value = null;
    try {
        return await promise;
    } catch (e) {
        error.value = (e as Error).message;
        return undefined;
    } finally {
        saving.value = false;
    }
}

const savePage = debounce(() => {
    void run(
        api('patch', base, {
            title: page.title,
            bio: page.bio,
            layout: page.layout,
            meta_title: page.meta_title,
            meta_description: page.meta_description,
            theme: page.theme,
        }),
    );
}, 600);

async function addBlock(type: string) {
    const block = await run(api<PublicBlock>('post', `${base}/blocks`, { type }));
    if (block) {
        blocks.push(block);
    }
}

const saveBlock = debounce((block: PublicBlock) => {
    void run(api('patch', `${base}/blocks/${block.id}`, { data: block.data, size: block.size, isVisible: block.isVisible }));
}, 600);

async function deleteBlock(block: PublicBlock) {
    const index = blocks.findIndex((b) => b.id === block.id);
    if (index !== -1) {
        blocks.splice(index, 1);
    }
    await run(api('delete', `${base}/blocks/${block.id}`));
}

function reorderBlocks() {
    void run(api('patch', `${base}/blocks/reorder`, { ids: blocks.map((b) => b.id) }));
}

async function addSocial(payload: { platform: string; url: string }) {
    const item = await run(api<SocialItem>('post', `${base}/social`, payload));
    if (item) {
        social.push(item);
    }
}

const saveSocial = debounce((item: SocialItem) => {
    void run(api('patch', `${base}/social/${item.id}`, { platform: item.platform, url: item.url }));
}, 600);

async function deleteSocial(item: SocialItem) {
    const index = social.findIndex((s) => s.id === item.id);
    if (index !== -1) {
        social.splice(index, 1);
    }
    await run(api('delete', `${base}/social/${item.id}`));
}

async function uploadAvatar(file: File) {
    const form = new FormData();
    form.append('avatar', file);
    const result = await run(api<{ avatarUrl: string | null }>('post', `${base}/avatar`, form));
    if (result) {
        page.avatarUrl = result.avatarUrl;
    }
}

async function removeAvatar() {
    await run(api('delete', `${base}/avatar`));
    page.avatarUrl = null;
}

async function togglePublish() {
    const next = page.status === 'published' ? 'draft' : 'published';
    const result = await run(api<{ status: string }>('patch', base, { status: next, title: page.title }));
    if (result) {
        page.status = result.status;
    }
}

async function copyUrl() {
    await navigator.clipboard.writeText(page.publicUrl);
    copied.value = true;
    setTimeout(() => (copied.value = false), 1500);
}

const isPublished = computed(() => page.status === 'published');
const profilePreview = computed(() => ({
    username: page.username,
    title: page.title || page.username,
    bio: page.bio,
    avatarUrl: page.avatarUrl,
}));
const socialPreview = computed(() => social.map((s) => ({ platform: s.platform, label: s.label, url: s.url })));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Editor', href: '#' },
];
</script>

<template>
    <Head title="Editor" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">
            <!-- Barra superior -->
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3">
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-muted-foreground">bioinfo.test/</span>
                    <span class="font-medium">{{ page.username }}</span>
                    <button type="button" class="rounded p-1 text-muted-foreground hover:bg-accent" title="Copiar URL" @click="copyUrl">
                        <Check v-if="copied" class="h-4 w-4 text-green-500" />
                        <Copy v-else class="h-4 w-4" />
                    </button>
                    <a :href="page.publicUrl" target="_blank" rel="noopener" class="rounded p-1 text-muted-foreground hover:bg-accent" title="Ver página">
                        <ExternalLink class="h-4 w-4" />
                    </a>
                </div>

                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <Loader2 v-if="saving" class="h-3.5 w-3.5 animate-spin" />
                        <span v-if="error" class="text-red-500">{{ error }}</span>
                        <span v-else-if="saving">Guardando…</span>
                        <span v-else>Guardado</span>
                    </span>
                    <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                        :class="isPublished ? 'bg-green-500/15 text-green-600 dark:text-green-400' : 'bg-amber-500/15 text-amber-600 dark:text-amber-400'"
                    >
                        {{ isPublished ? 'Publicada' : 'Borrador' }}
                    </span>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-medium text-white transition"
                        :class="isPublished ? 'bg-neutral-600 hover:bg-neutral-700' : 'bg-green-600 hover:bg-green-700'"
                        @click="togglePublish"
                    >
                        {{ isPublished ? 'Despublicar' : 'Publicar' }}
                    </button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
                <!-- Columna de edición -->
                <div class="space-y-6">
                    <section class="rounded-xl border p-4">
                        <h2 class="mb-3 text-sm font-semibold">Perfil</h2>
                        <ProfilePanel :page="page" @change="savePage" @avatar="uploadAvatar" @avatar-remove="removeAvatar" />
                    </section>

                    <section class="rounded-xl border p-4">
                        <h2 class="mb-3 text-sm font-semibold">Bloques</h2>
                        <div class="space-y-3">
                            <AddBlockMenu @add="addBlock" />
                            <BlockListEditor :blocks="blocks" :layout="page.layout" @reorder="reorderBlocks" @update="saveBlock" @delete="deleteBlock" />
                        </div>
                    </section>

                    <section class="rounded-xl border p-4">
                        <h2 class="mb-3 text-sm font-semibold">Redes sociales</h2>
                        <SocialLinksPanel :social="social" :platforms="socialPlatforms" @add="addSocial" @update="saveSocial" @delete="deleteSocial" />
                    </section>

                    <section class="rounded-xl border p-4">
                        <h2 class="mb-3 text-sm font-semibold">Apariencia</h2>
                        <ThemePanel :page="page" :presets="presets" @change="savePage" />
                    </section>
                </div>

                <!-- Preview -->
                <div class="lg:sticky lg:top-4 lg:self-start">
                    <PhonePreview
                        :profile="profilePreview"
                        :layout="page.layout"
                        :blocks="blocks"
                        :social="socialPreview"
                        :theme="page.theme"
                        :presets="presets"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
