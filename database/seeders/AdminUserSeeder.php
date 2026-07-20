<?php

namespace Database\Seeders;

use App\Enums\PageLayout;
use App\Enums\PageStatus;
use App\Enums\Role;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@bioinfo.test'],
            [
                'name' => 'Administrador',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles([Role::Admin->value]);

        Page::firstOrCreate(
            ['user_id' => $admin->id, 'slug' => 'home'],
            [
                'title' => 'Administrador',
                'bio' => 'Cuenta de administración de Bioinfo.',
                'layout' => PageLayout::List->value,
                'status' => PageStatus::Published->value,
                'is_primary' => true,
                'published_at' => now(),
            ],
        );
    }
}
