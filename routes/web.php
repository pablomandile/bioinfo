<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Dashboard\AnalyticsController;
use App\Http\Controllers\Dashboard\AvatarController;
use App\Http\Controllers\Dashboard\BlockController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\PageController;
use App\Http\Controllers\Dashboard\SocialLinkController;
use App\Http\Controllers\Public\LinkRedirectController;
use App\Http\Controllers\Public\PublicPageController;
use App\Http\Controllers\Public\QrController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('dashboard')->scopeBindings()->group(function () {
        Route::get('pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
        Route::get('pages/{page}/analytics', [AnalyticsController::class, 'show'])->name('pages.analytics');
        Route::patch('pages/{page}', [PageController::class, 'update'])->name('pages.update');

        Route::post('pages/{page}/blocks', [BlockController::class, 'store'])->name('blocks.store');
        Route::patch('pages/{page}/blocks/reorder', [BlockController::class, 'reorder'])->name('blocks.reorder');
        Route::patch('pages/{page}/blocks/{block}', [BlockController::class, 'update'])->name('blocks.update');
        Route::delete('pages/{page}/blocks/{block}', [BlockController::class, 'destroy'])->name('blocks.destroy');

        Route::post('pages/{page}/social', [SocialLinkController::class, 'store'])->name('social.store');
        Route::patch('pages/{page}/social/{socialLink}', [SocialLinkController::class, 'update'])->name('social.update');
        Route::delete('pages/{page}/social/{socialLink}', [SocialLinkController::class, 'destroy'])->name('social.destroy');

        Route::post('pages/{page}/avatar', [AvatarController::class, 'store'])->name('avatar.store');
        Route::delete('pages/{page}/avatar', [AvatarController::class, 'destroy'])->name('avatar.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Área de administración (solo rol admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::get('settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::patch('settings', [AdminSettingController::class, 'update'])->name('settings.update');
});

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

Route::get('{username}/qr.svg', QrController::class)
    ->where('username', '[A-Za-z0-9_.\-]+')
    ->name('public.qr');

Route::get('{username}', [PublicPageController::class, 'show'])
    ->where('username', '[A-Za-z0-9_.\-]+')
    ->name('public.page');
