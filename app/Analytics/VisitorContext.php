<?php

namespace App\Analytics;

use Illuminate\Support\Carbon;

/**
 * Deriva el contexto (anonimizado) de un visitante para las analíticas.
 * No almacena PII: la IP solo se guarda como hash irreversible con salt diario.
 */
class VisitorContext
{
    public static function ipHash(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }

        $salt = config('app.key').'|'.Carbon::now()->toDateString();

        return hash('sha256', $ip.'|'.$salt);
    }

    public static function deviceType(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        $ua = strtolower($userAgent);

        return match (true) {
            (bool) preg_match('/bot|crawler|spider|crawling|slurp|bingpreview|facebookexternalhit|embedly/', $ua) => 'bot',
            str_contains($ua, 'ipad'), str_contains($ua, 'tablet') => 'tablet',
            str_contains($ua, 'mobi'), str_contains($ua, 'android') => 'mobile',
            default => 'desktop',
        };
    }

    public static function referrerHost(?string $referer): ?string
    {
        if (! $referer) {
            return null;
        }

        return parse_url($referer, PHP_URL_HOST) ?: null;
    }
}
