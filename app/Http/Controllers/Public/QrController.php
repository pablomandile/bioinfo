<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Http\Response;

class QrController extends Controller
{
    public function __invoke(QrService $qr, string $username, ?string $slug = null): Response
    {
        $user = User::query()
            ->whereRaw('LOWER(username) = ?', [strtolower($username)])
            ->where('is_active', true)
            ->first();

        abort_unless($user, 404);

        $page = $slug === null
            ? $user->pages()->where('is_primary', true)->first()
            : $user->pages()->where('slug', $slug)->first();

        // BR-9.1: el QR solo está disponible para páginas publicadas.
        abort_unless($page && $page->isPublished(), 404);

        $publicUrl = $page->is_primary
            ? url('/'.$user->username)
            : url('/'.$user->username.'/'.$page->slug);

        return response($qr->svg($publicUrl), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
