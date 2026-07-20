<?php

namespace App\Http\Middleware;

use App\Settings\RegistrationSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationOpen
{
    public function __construct(private readonly RegistrationSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->open) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
