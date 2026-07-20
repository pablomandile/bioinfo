<?php

namespace App\Http\Controllers\Public;

use App\Enums\SocialPlatform;
use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Page;
use App\Models\User;
use App\Services\ThemeResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    /**
     * Slugs que nunca deben resolverse como username público.
     *
     * @var array<int, string>
     */
    private const RESERVED = [
        'dashboard', 'admin', 'api', 'login', 'register', 'logout',
        'go', 'storage', 'build', 'settings', 'password', 'up', 'home',
        'email', 'verify-email', 'confirm-password', 'forgot-password', 'reset-password',
    ];

    public function __construct(private readonly ThemeResolver $themes) {}

    public function show(Request $request, string $username): Response
    {
        abort_if(in_array(strtolower($username), self::RESERVED, true), 404);

        $user = User::query()
            ->whereRaw('LOWER(username) = ?', [strtolower($username)])
            ->where('is_active', true)
            ->first();

        abort_unless($user, 404);

        /** @var Page|null $page */
        $page = $user->pages()->where('is_primary', true)->first();

        abort_unless($page, 404);

        $viewer = $request->user();
        $isOwner = $viewer !== null && ($viewer->id === $user->id || $viewer->isAdmin());

        abort_if(! $page->isPublished() && ! $isOwner, 404);

        $page->load([
            'blocks' => fn ($query) => $isOwner ? $query : $query->visible(),
            'socialLinks',
        ]);

        $theme = $this->themes->resolve($page->theme);
        $avatarUrl = $page->getFirstMediaUrl('avatar') ?: null;

        return Inertia::render('public/Show', [
            'profile' => [
                'username' => $user->username,
                'title' => $page->title ?: $user->name,
                'bio' => $page->bio,
                'avatarUrl' => $avatarUrl,
            ],
            'layout' => $page->layout->value,
            'blocks' => $page->blocks->map(fn (Block $block) => [
                'id' => $block->public_id,
                'type' => $block->type->value,
                'data' => $block->data ?? [],
                'size' => $block->size->value,
                'gridColSpan' => $block->grid_col_span,
                'gridRowSpan' => $block->grid_row_span,
                'isVisible' => $block->is_visible,
            ])->values(),
            'social' => $page->socialLinks->map(fn ($link) => [
                'platform' => $link->platform->value,
                'label' => $link->platform instanceof SocialPlatform ? $link->platform->label() : (string) $link->platform,
                'url' => $link->url,
            ])->values(),
            'theme' => [
                'mode' => $theme['mode'],
                'cssVars' => $this->themes->cssVars($theme['tokens']),
            ],
            'meta' => [
                'title' => $page->meta_title ?: ($page->title ?: $user->name),
                'description' => $page->meta_description ?: $page->bio,
                'ogImage' => ($page->getFirstMediaUrl('og') ?: $avatarUrl),
                'noindex' => ! $page->isPublished(),
            ],
            'isOwnerPreview' => $isOwner && ! $page->isPublished(),
        ]);
    }
}
