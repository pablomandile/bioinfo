import type { PublicTheme, ThemeMode } from '@/types/bio';

export interface ThemePreset {
    id: string;
    name: string;
    settings: {
        mode?: ThemeMode;
        tokens?: Record<string, string>;
    };
}

export interface PageTheme {
    presetId: string | null;
    mode?: ThemeMode | 'auto';
    tokens?: Record<string, string>;
}

const TOKEN_MAP: Record<string, string> = {
    bg: '--bio-bg',
    bg_image: '--bio-bg-image',
    fg: '--bio-fg',
    btn_bg: '--bio-btn-bg',
    btn_fg: '--bio-btn-fg',
    btn_border: '--bio-btn-border',
    btn_radius: '--bio-btn-radius',
    btn_shadow: '--bio-btn-shadow',
    accent: '--bio-accent',
    font: '--bio-font',
    card_bg: '--bio-card-bg',
};

function filterEmpty(tokens: Record<string, string>): Record<string, string> {
    return Object.fromEntries(Object.entries(tokens).filter(([, value]) => value !== null && value !== ''));
}

export function tokensToCssVars(tokens: Record<string, string>): Record<string, string> {
    const vars: Record<string, string> = {};
    for (const [key, cssVar] of Object.entries(TOKEN_MAP)) {
        if (tokens[key]) {
            vars[cssVar] = tokens[key];
        }
    }
    return vars;
}

/**
 * Resuelve el tema de una página (preset + overrides) a la forma que consume
 * PublicLayout. Espejo cliente de App\Services\ThemeResolver.
 */
export function resolveTheme(theme: PageTheme | null, presets: ThemePreset[]): PublicTheme {
    const preset = theme?.presetId ? presets.find((p) => p.id === theme.presetId) : undefined;
    const presetTokens = preset?.settings?.tokens ?? {};
    const overrides = filterEmpty(theme?.tokens ?? {});
    const tokens = { ...presetTokens, ...overrides };

    const rawMode = theme?.mode ?? preset?.settings?.mode ?? 'light';
    const mode: ThemeMode = rawMode === 'dark' ? 'dark' : 'light';

    return { mode, cssVars: tokensToCssVars(tokens) };
}
