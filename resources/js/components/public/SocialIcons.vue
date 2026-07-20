<script setup lang="ts">
import type { PublicSocial } from '@/types/bio';
import { Facebook, Github, Globe, Instagram, Linkedin, Mail, MessageCircle, Music, Twitter, Youtube } from 'lucide-vue-next';
import type { Component } from 'vue';

defineProps<{ links: PublicSocial[] }>();

const iconMap: Record<string, Component> = {
    instagram: Instagram,
    x: Twitter,
    tiktok: Music,
    youtube: Youtube,
    facebook: Facebook,
    linkedin: Linkedin,
    github: Github,
    whatsapp: MessageCircle,
    email: Mail,
    website: Globe,
};
</script>

<template>
    <div v-if="links.length" class="flex flex-wrap items-center justify-center gap-4">
        <a
            v-for="link in links"
            :key="link.platform"
            :href="link.url"
            target="_blank"
            rel="noopener noreferrer"
            :aria-label="link.label"
            class="transition hover:opacity-70"
            :style="{ color: 'var(--bio-fg)' }"
        >
            <component :is="iconMap[link.platform] ?? Globe" class="h-6 w-6" />
        </a>
    </div>
</template>
