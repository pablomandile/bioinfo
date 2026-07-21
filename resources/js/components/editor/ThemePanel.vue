<script setup lang="ts">
import { BACKGROUNDS, type BackgroundPreset } from '@/lib/backgrounds';
import type { PageTheme, ThemePreset } from '@/lib/theme';
import { LayoutGrid, List } from 'lucide-vue-next';

interface EditablePage {
    theme: PageTheme;
    layout: 'list' | 'grid';
}

const props = defineProps<{ page: EditablePage; presets: ThemePreset[] }>();
const emit = defineEmits<{ (e: 'change'): void }>();

function selectPreset(preset: ThemePreset) {
    props.page.theme.presetId = preset.id;
    props.page.theme.mode = preset.settings?.mode ?? 'light';
    // Un preset define un look completo: se limpian los overrides de fondo.
    props.page.theme.tokens = {};
    emit('change');
}

function selectBackground(background: BackgroundPreset) {
    // Sobreescribe el fondo y adapta texto/botones para mantener el contraste.
    props.page.theme.tokens = {
        bg: background.value,
        fg: '#ffffff',
        btn_bg: 'rgba(255, 255, 255, 0.15)',
        btn_fg: '#ffffff',
        btn_border: 'rgba(255, 255, 255, 0.30)',
        card_bg: 'rgba(255, 255, 255, 0.12)',
    };
    props.page.theme.mode = 'dark';
    emit('change');
}

function isBackgroundSelected(background: BackgroundPreset): boolean {
    return props.page.theme.tokens?.bg === background.value;
}

function setLayout(layout: 'list' | 'grid') {
    props.page.layout = layout;
    emit('change');
}

function swatch(preset: ThemePreset) {
    const tokens = preset.settings?.tokens ?? {};
    return {
        background: tokens.bg ?? '#ffffff',
        color: tokens.fg ?? '#111827',
    };
}
</script>

<template>
    <div class="space-y-5">
        <div>
            <p class="mb-2 text-xs font-medium text-muted-foreground">Tema</p>
            <div class="grid grid-cols-2 gap-2">
                <button
                    v-for="preset in presets"
                    :key="preset.id"
                    type="button"
                    class="rounded-lg border-2 p-3 text-left transition"
                    :class="page.theme.presetId === preset.id ? 'border-primary' : 'border-transparent hover:border-border'"
                    :style="swatch(preset)"
                    @click="selectPreset(preset)"
                >
                    <span class="text-xs font-semibold">{{ preset.name }}</span>
                    <span
                        class="mt-2 block rounded-md py-1.5 text-center text-[10px]"
                        :style="{ background: preset.settings?.tokens?.btn_bg, color: preset.settings?.tokens?.btn_fg }"
                    >
                        Botón
                    </span>
                </button>
            </div>
        </div>

        <div>
            <p class="mb-2 text-xs font-medium text-muted-foreground">Fondo</p>
            <div class="grid grid-cols-5 gap-2">
                <button
                    v-for="background in BACKGROUNDS"
                    :key="background.id"
                    type="button"
                    class="h-12 rounded-lg border-2 transition"
                    :class="isBackgroundSelected(background) ? 'border-primary ring-2 ring-primary/40' : 'border-transparent hover:border-border'"
                    :style="{ background: background.value }"
                    :title="background.name"
                    @click="selectBackground(background)"
                />
            </div>
        </div>

        <div>
            <p class="mb-2 text-xs font-medium text-muted-foreground">Disposición</p>
            <div class="grid grid-cols-2 gap-2">
                <button
                    type="button"
                    class="flex items-center justify-center gap-2 rounded-md border py-2 text-sm transition"
                    :class="page.layout === 'list' ? 'border-primary bg-accent' : 'hover:bg-accent'"
                    @click="setLayout('list')"
                >
                    <List class="h-4 w-4" /> Lista
                </button>
                <button
                    type="button"
                    class="flex items-center justify-center gap-2 rounded-md border py-2 text-sm transition"
                    :class="page.layout === 'grid' ? 'border-primary bg-accent' : 'hover:bg-accent'"
                    @click="setLayout('grid')"
                >
                    <LayoutGrid class="h-4 w-4" /> Grid
                </button>
            </div>
        </div>
    </div>
</template>
