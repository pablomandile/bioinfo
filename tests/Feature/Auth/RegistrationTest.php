<?php

namespace Tests\Feature\Auth;

use App\Settings\RegistrationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function openRegistration(): void
    {
        $settings = app(RegistrationSettings::class);
        $settings->open = true;
        $settings->save();
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->openRegistration();

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->openRegistration();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_is_blocked_when_closed(): void
    {
        // Cerrado por defecto (RegistrationSettings::open = false).
        $this->get('/register')->assertRedirect(route('login'));

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
