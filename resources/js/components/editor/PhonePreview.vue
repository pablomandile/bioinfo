<script setup lang="ts">
import ProfileHeader from '@/components/public/ProfileHeader.vue';
import SocialIcons from '@/components/public/SocialIcons.vue';
import { resolveBlock } from '@/lib/blocks/registry';
import { resolveTheme, type PageTheme, type ThemePreset } from '@/lib/theme';
import type { PublicBlock, PublicProfile, PublicSocial } from '@/types/bio';
import { computed } from 'vue';

const props = defineProps<{
    profile: PublicProfile;
    layout: 'list' | 'grid';
    blocks: PublicBlock[];
    social: PublicSocial[];
    theme: PageTheme;
    presets: ThemePreset[];
}>();

const resolved = computed(() => resolveTheme(props.theme, props.presets));

const rootStyle = computed(() => ({
    ...resolved.value.cssVars,
    background: 'var(--bio-bg, #ffffff)',
    color: 'var(--bio-fg, #111827)',
    fontFamily: 'var(--bio-font, Inter), ui-sans-serif, system-ui, sans-serif',
}));

const visibleBlocks = computed(() => props.blocks.filter((block) => block.isVisible));

function wrapperClass(size: string): string {
    if (props.layout !== 'grid') {
        return '';
    }
    return size === 'lg' ? 'col-span-2' : 'col-span-1';
}
</script>

<template>
    <div class="mx-auto w-full max-w-[360px]">
        <div class="overflow-hidden rounded-[2.25rem] border-[6px] border-neutral-800 shadow-2xl">
            <div :style="rootStyle" class="h-[640px] overflow-y-auto px-5 py-8">
                <div class="pointer-events-none select-none">
                    <ProfileHeader :profile="profile" />
                    <SocialIcons :links="social" class="mt-4" />
                    <div class="mt-6" :class="layout === 'grid' ? 'grid grid-cols-2 gap-3' : 'flex flex-col gap-3'">
                        <template v-for="block in visibleBlocks" :key="block.id">
                            <div v-if="resolveBlock(block.type)" :class="wrapperClass(block.size)">
                                <component :is="resolveBlock(block.type)!.renderer" :block="block" />
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
