<?php

use App\Http\Controllers\Public\LinkRedirectController;
use App\Http\Controllers\Public\PublicPageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Rutas públicas del biolink
|--------------------------------------------------------------------------
| La ruta comodín /{username} debe registrarse al final para no colisionar
| con /dashboard, /login, /settings, etc.
*/
Route::get('go/{block}', LinkRedirectController::class)->name('go');

Route::get('{username}', [PublicPageController::class, 'show'])
    ->where('username', '[A-Za-z0-9_.\-]+')
    ->name('public.page');
