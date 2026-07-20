<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Http\Response;

class QrController extends Controller
{
    public function __invoke(string $username, QrService $qr): Response
    {
        $user = User::query()
            ->whereRaw('LOWER(username) = ?', [strtolower($username)])
            ->where('is_active', true)
            ->first();

        abort_unless($user, 404);

        $page = $user->pages()->where('is_primary', true)->first();

        // BR-9.1: el QR solo está disponible para páginas publicadas.
        abort_unless($page && $page->isPublished(), 404);

        return response($qr->svg(url('/'.$user->username)), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
