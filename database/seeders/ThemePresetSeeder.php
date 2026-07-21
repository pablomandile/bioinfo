<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemePresetSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->presets() as $preset) {
            Theme::updateOrCreate(
                ['name' => $preset['name'], 'is_preset' => true],
                ['user_id' => null, 'settings' => $preset['settings']],
            );
        }
    }

    /**
     * @return array<int, array{name: string, settings: array<string, mixed>}>
     */
    private function presets(): array
    {
        return [
            [
                'name' => 'Violeta',
                'settings' => [
                    'mode' => 'dark',
                    'tokens' => [
                        'bg' => 'linear-gradient(160deg, #6d28d9 0%, #9333ea 45%, #db2777 100%)',
                        'fg' => '#ffffff',
                        'btn_bg' => 'rgba(255, 255, 255, 0.15)',
                        'btn_fg' => '#ffffff',
                        'btn_border' => 'rgba(255, 255, 255, 0.30)',
                        'btn_radius' => '1rem',
                        'btn_shadow' => '0 8px 24px rgba(0, 0, 0, 0.20)',
                        'accent' => '#f0abfc',
                        'font' => 'Inter',
                        'card_bg' => 'rgba(255, 255, 255, 0.12)',
                    ],
                ],
            ],
            [
                'name' => 'Clean Light',
                'settings' => [
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
                ],
            ],
            [
                'name' => 'Midnight',
                'settings' => [
                    'mode' => 'dark',
                    'tokens' => [
                        'bg' => '#0b1020',
                        'fg' => '#e5e7eb',
                        'btn_bg' => '#1f2937',
                        'btn_fg' => '#ffffff',
                        'btn_border' => 'transparent',
                        'btn_radius' => '0.75rem',
                        'btn_shadow' => 'none',
                        'accent' => '#38bdf8',
                        'font' => 'Inter',
                        'card_bg' => '#111827',
                    ],
                ],
            ],
            [
                'name' => 'Sunset',
                'settings' => [
                    'mode' => 'light',
                    'tokens' => [
                        'bg' => 'linear-gradient(160deg, #f97316 0%, #db2777 100%)',
                        'fg' => '#ffffff',
                        'btn_bg' => 'rgba(255,255,255,0.15)',
                        'btn_fg' => '#ffffff',
                        'btn_border' => 'rgba(255,255,255,0.35)',
                        'btn_radius' => '1rem',
                        'btn_shadow' => '0 6px 20px rgba(0,0,0,0.15)',
                        'accent' => '#fde047',
                        'font' => 'Poppins',
                        'card_bg' => 'rgba(255,255,255,0.12)',
                    ],
                ],
            ],
            [
                'name' => 'Mono Dark',
                'settings' => [
                    'mode' => 'dark',
                    'tokens' => [
                        'bg' => '#000000',
                        'fg' => '#fafafa',
                        'btn_bg' => 'transparent',
                        'btn_fg' => '#fafafa',
                        'btn_border' => '#fafafa',
                        'btn_radius' => '0.25rem',
                        'btn_shadow' => 'none',
                        'accent' => '#a3a3a3',
                        'font' => 'JetBrains Mono',
                        'card_bg' => '#0a0a0a',
                    ],
                ],
            ],
        ];
    }
}
