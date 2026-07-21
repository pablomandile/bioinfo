<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Settings\RegistrationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Usernames que no pueden asignarse automáticamente (colisionan con rutas).
     *
     * @var array<int, string>
     */
    private const RESERVED = [
        'admin', 'dashboard', 'api', 'login', 'register', 'logout',
        'go', 'storage', 'build', 'settings', 'password', 'up', 'home',
    ];

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(RegistrationSettings $registration): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors([
                'email' => 'No pudimos completar el acceso con Google. Intentá de nuevo.',
            ]);
        }

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if (! $user) {
            // Cuenta nueva: respeta la configuración de registro abierto/cerrado (BR-2.6).
            if (! $registration->open) {
                return redirect()->route('login')->withErrors([
                    'email' => 'El registro está cerrado por el momento.',
                ]);
            }

            $user = User::create([
                'name' => $googleUser->getName() ?: 'Usuario',
                'username' => $this->uniqueUsername($googleUser->getEmail(), $googleUser->getName()),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'is_active' => true,
            ]);
            $user->email_verified_at = now();
            $user->save();
            $user->assignRole(Role::User->value);
        } elseif (! $user->google_id) {
            // Cuenta existente (por email): se vincula la identidad de Google.
            $user->update(['google_id' => $googleUser->getId()]);
        }

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta está desactivada.',
            ]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }

    private function uniqueUsername(?string $email, ?string $name): string
    {
        $source = $email ? Str::before($email, '@') : ($name ?? 'usuario');
        $base = Str::of($source)->lower()->slug('-')->limit(24, '')->toString();

        if (strlen($base) < 3) {
            $base = 'user-'.$base;
        }

        $username = $base;
        $counter = 1;

        while (in_array($username, self::RESERVED, true) || User::where('username', $username)->exists()) {
            $counter++;
            $username = Str::of($base)->limit(22, '')->toString().'-'.$counter;
        }

        return $username;
    }
}
