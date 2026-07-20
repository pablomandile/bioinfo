<script setup lang="ts">
import ProfileHeader from '@/components/public/ProfileHeader.vue';
import SocialIcons from '@/components/public/SocialIcons.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { resolveBlock } from '@/lib/blocks/registry';
import type { PublicPageProps } from '@/types/bio';
import { Head } from '@inertiajs/vue3';

const props = defineProps<PublicPageProps>();

function blockWrapperClass(size: string): string {
    if (props.layout !== 'grid') {
        return '';
    }

    return size === 'lg' ? 'col-span-2' : 'col-span-1';
}
</script>

<template>
    <Head>
        <title>{{ meta.title }}</title>
    </Head>

    <PublicLayout :theme="theme">
        <div
            v-if="isOwnerPreview"
            class="mb-4 rounded-md bg-amber-500/90 px-3 py-2 text-center text-xs font-medium text-white"
        >
            Vista previa — esta página está en borrador y todavía no es pública.
        </div>

        <ProfileHeader :profile="profile" />

        <SocialIcons :links="social" class="mt-5" />

        <div class="mt-8" :class="layout === 'grid' ? 'grid grid-cols-2 gap-3' : 'flex flex-col gap-3'">
            <template v-for="block in blocks" :key="block.id">
                <div v-if="resolveBlock(block.type)" :class="blockWrapperClass(block.size)">
                    <component :is="resolveBlock(block.type)!.renderer" :block="block" />
                </div>
            </template>
        </div>

        <div class="mt-auto pt-10 text-center text-xs opacity-60" :style="{ color: 'var(--bio-fg)' }">
            <a href="/" class="hover:underline">Hecho con Bioinfo</a>
        </div>
    </PublicLayout>
</template>
