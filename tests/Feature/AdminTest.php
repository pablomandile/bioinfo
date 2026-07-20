<?php

namespace Tests\Feature;

use App\Models\User;
use App\Settings\RegistrationSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(string $username = 'admin'): User
    {
        $user = User::factory()->create(['username' => $username]);
        $user->assignRole('admin');

        return $user;
    }

    private function member(?string $username = null): User
    {
        $user = User::factory()->create(['username' => $username]);
        $user->assignRole('user');

        return $user;
    }

    public function test_non_admin_cannot_access_the_admin_area(): void
    {
        $this->actingAs($this->member())->get('/admin')->assertForbidden();
    }

    public function test_admin_can_access_the_admin_pages(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/Dashboard'));
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/settings')->assertOk();
    }

    public function test_admin_can_change_a_user_role(): void
    {
        $admin = $this->admin();
        $user = $this->member('member');

        $this->actingAs($admin)->patch("/admin/users/{$user->id}", ['role' => 'admin'])->assertRedirect();

        $this->assertTrue($user->fresh()->isAdmin());
    }

    public function test_admin_can_deactivate_a_user(): void
    {
        $admin = $this->admin();
        $user = $this->member('member');

        $this->actingAs($admin)->patch("/admin/users/{$user->id}", ['is_active' => false])->assertRedirect();

        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_cannot_deactivate_the_last_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch("/admin/users/{$admin->id}", ['is_active' => false])
            ->assertSessionHasErrors('user');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_cannot_demote_the_last_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch("/admin/users/{$admin->id}", ['role' => 'user'])
            ->assertSessionHasErrors('user');

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_admin_can_update_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch('/admin/settings', ['siteName' => 'Nuevo Nombre', 'registrationOpen' => true])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', ['group' => 'registration', 'name' => 'open', 'payload' => 'true']);
    }

    public function test_settings_update_requires_admin(): void
    {
        $this->actingAs($this->member())
            ->patch('/admin/settings', ['siteName' => 'Hack', 'registrationOpen' => true])
            ->assertForbidden();
    }
}
