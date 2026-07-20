<script setup lang="ts">
import type { PublicProfile } from '@/types/bio';
import { computed } from 'vue';

const props = defineProps<{ profile: PublicProfile }>();

const initials = computed(() => {
    const base = props.profile.title || props.profile.username;
    return base
        .split(/\s+/)
        .map((word) => word.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase();
});
</script>

<template>
    <div class="flex flex-col items-center gap-3 text-center">
        <div class="h-24 w-24 overflow-hidden rounded-full border-2" :style="{ borderColor: 'var(--bio-accent, #6366f1)' }">
            <img v-if="profile.avatarUrl" :src="profile.avatarUrl" :alt="profile.title" class="h-full w-full object-cover" />
            <div
                v-else
                class="flex h-full w-full items-center justify-center text-2xl font-semibold"
                :style="{ background: 'var(--bio-card-bg, rgba(0,0,0,0.06))', color: 'var(--bio-fg)' }"
            >
                {{ initials }}
            </div>
        </div>

        <h1 class="text-xl font-bold" :style="{ color: 'var(--bio-fg)' }">{{ profile.title }}</h1>
        <p v-if="profile.bio" class="max-w-sm text-sm opacity-90" :style="{ color: 'var(--bio-fg)' }">{{ profile.bio }}</p>
    </div>
</template>
