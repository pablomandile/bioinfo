<?php

namespace Database\Seeders;

use App\Enums\PageLayout;
use App\Enums\PageStatus;
use App\Enums\Role;
use App\Models\Page;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::updateOrCreate(
            ['email' => 'demo@bioinfo.test'],
            [
                'name' => 'Demo Creator',
                'username' => 'demo',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $demo->syncRoles([Role::User->value]);

        $theme = Theme::where('is_preset', true)->where('name', 'Violeta')->first();

        $page = Page::updateOrCreate(
            ['user_id' => $demo->id, 'slug' => 'home'],
            [
                'title' => 'Demo Creator',
                'bio' => 'Bienvenidos a mi biolink 👋',
                'layout' => PageLayout::List->value,
                'status' => PageStatus::Published->value,
                'is_primary' => true,
                'published_at' => now(),
                'theme' => [
                    'presetId' => $theme?->slug,
                    'mode' => 'light',
                    'tokens' => [],
                ],
            ],
        );

        if ($page->blocks()->count() === 0) {
            $blocks = [
                ['type' => 'heading', 'position' => 1, 'data' => ['text' => 'Mis enlaces']],
                ['type' => 'link', 'position' => 2, 'data' => ['label' => 'Mi sitio web', 'url' => 'https://example.com']],
                ['type' => 'link', 'position' => 3, 'data' => ['label' => 'Mi tienda', 'url' => 'https://example.com/shop']],
                ['type' => 'text', 'position' => 4, 'data' => ['text' => 'Gracias por visitar mi perfil.']],
                ['type' => 'embed', 'position' => 5, 'data' => ['provider' => 'youtube', 'id' => 'dQw4w9WgXcQ']],
            ];

            foreach ($blocks as $block) {
                $page->blocks()->create($block);
            }

            $socialLinks = [
                ['platform' => 'instagram', 'url' => 'https://instagram.com/demo', 'position' => 1],
                ['platform' => 'x', 'url' => 'https://x.com/demo', 'position' => 2],
                ['platform' => 'tiktok', 'url' => 'https://tiktok.com/@demo', 'position' => 3],
            ];

            foreach ($socialLinks as $link) {
                $page->socialLinks()->create($link);
            }
        }
    }
}
