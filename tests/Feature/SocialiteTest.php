<?php

namespace Tests\Feature;

use App\Models\User;
use App\Settings\RegistrationSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialiteTest extends TestCase
{
    use RefreshDatabase;

    private function mockGoogleUser(string $id, string $email, string $name = 'Google User'): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function openRegistration(): void
    {
        $settings = app(RegistrationSettings::class);
        $settings->open = true;
        $settings->save();
    }

    public function test_new_google_user_is_created_and_logged_in(): void
    {
        $this->seed(RoleSeeder::class);
        $this->openRegistration();
        $this->mockGoogleUser('999', 'nuevo@example.com', 'Nuevo Usuario');

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'nuevo@example.com', 'google_id' => '999']);
    }

    public function test_new_google_user_is_blocked_when_registration_is_closed(): void
    {
        $this->seed(RoleSeeder::class);
        // El registro está cerrado por defecto.
        $this->mockGoogleUser('123', 'bloqueado@example.com');

        $this->get('/auth/google/callback')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'bloqueado@example.com']);
    }

    public function test_existing_user_links_google_and_logs_in(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create(['email' => 'pablo@example.com', 'username' => 'pablo', 'google_id' => null]);
        $this->mockGoogleUser('555', 'pablo@example.com', 'Pablo');

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame('555', $user->fresh()->google_id);
    }
}
