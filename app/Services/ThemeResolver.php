<?php

namespace App\Services;

use App\Models\Theme;

/**
 * Resuelve el tema de una página (preset + overrides) a un conjunto final de
 * tokens y a las CSS variables que consume la página pública.
 */
class ThemeResolver
{
    /**
     * Mapa token interno => CSS variable pública.
     *
     * @var array<string, string>
     */
    private const TOKEN_MAP = [
        'bg' => '--bio-bg',
        'bg_image' => '--bio-bg-image',
        'fg' => '--bio-fg',
        'btn_bg' => '--bio-btn-bg',
        'btn_fg' => '--bio-btn-fg',
        'btn_border' => '--bio-btn-border',
        'btn_radius' => '--bio-btn-radius',
        'btn_shadow' => '--bio-btn-shadow',
        'accent' => '--bio-accent',
        'font' => '--bio-font',
        'card_bg' => '--bio-card-bg',
    ];

    /**
     * @param  array<string, mixed>|null  $theme  El campo pages.theme ({presetId, mode, tokens}).
     * @return array{mode: string, tokens: array<string, string>}
     */
    public function resolve(?array $theme): array
    {
        $theme ??= [];

        $preset = ! empty($theme['presetId'])
            ? Theme::query()->where('is_preset', true)->where('slug', $theme['presetId'])->first()
            : null;

        $presetSettings = $preset?->settings ?? $this->defaultSettings();

        $overrides = array_filter(
            $theme['tokens'] ?? [],
            fn ($value) => $value !== null && $value !== '',
        );

        return [
            'mode' => $theme['mode'] ?? ($presetSettings['mode'] ?? 'light'),
            'tokens' => array_merge($presetSettings['tokens'] ?? [], $overrides),
        ];
    }

    /**
     * @param  array<string, string>  $tokens
     * @return array<string, string>
     */
    public function cssVars(array $tokens): array
    {
        $vars = [];

        foreach (self::TOKEN_MAP as $key => $cssVar) {
            if (isset($tokens[$key]) && $tokens[$key] !== '') {
                $vars[$cssVar] = $tokens[$key];
            }
        }

        return $vars;
    }

    /**
     * @return array{mode: string, tokens: array<string, string>}
     */
    private function defaultSettings(): array
    {
        return [
            'mode' => 'light',
            'tokens' => [
                'bg' => '#ffffff',
                'fg' => '#111827',
                'btn_bg' => '#111827',
                'btn_fg' => '#ffffff',
                'btn_border' => 'transparent',
                'btn_radius' => '9999px',
                'btn_shadow' => 'none',
                'accent' => '#6366f1',
                'font' => 'Inter',
                'card_bg' => '#f9fafb',
            ],
        ];
    }
}
