<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\BlockType;
use App\Enums\PageLayout;
use App\Enums\PageStatus;
use App\Enums\SocialPlatform;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Page;
use App\Models\SocialLink;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /** Máximo de biolinks por usuario (BR-3.2). */
    public const MAX_PAGES = 4;

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->pages()->count() >= self::MAX_PAGES) {
            return back()->withErrors(['pages' => 'Alcanzaste el máximo de '.self::MAX_PAGES.' biolinks.']);
        }

        $page = $user->pages()->create([
            'slug' => $this->uniqueSlug($user),
            'title' => 'Nuevo biolink',
            'layout' => PageLayout::List->value,
            'status' => PageStatus::Draft->value,
            'is_primary' => $user->pages()->count() === 0,
            'theme' => ['presetId' => null, 'mode' => 'dark', 'tokens' => []],
        ]);

        return redirect()->route('pages.edit', $page);
    }

    public function edit(Request $request, Page $page): Response
    {
        $this->authorize('update', $page);

        $page->load(['blocks', 'socialLinks', 'user']);

        return Inertia::render('editor/Edit', [
            'page' => [
                'id' => $page->id,
                'username' => $page->user->username,
                'slug' => $page->slug,
                'isPrimary' => $page->is_primary,
                'title' => $page->title,
                'bio' => $page->bio,
                'avatarUrl' => $page->getFirstMediaUrl('avatar') ?: null,
                'layout' => $page->layout->value,
                'status' => $page->status->value,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'theme' => $page->theme ?? ['presetId' => null, 'mode' => 'light', 'tokens' => []],
                'publicUrl' => $this->publicUrl($page),
                'qrUrl' => $this->qrUrl($page),
            ],
            'blocks' => $page->blocks->map(fn ($block) => $block->toPublicArray())->values(),
            'social' => $page->socialLinks->map(fn (SocialLink $link) => [
                'id' => $link->id,
                'platform' => $link->platform->value,
                'label' => $link->platform->label(),
                'url' => $link->url,
            ])->values(),
            'presets' => Theme::query()->where('is_preset', true)->get()->map(fn (Theme $theme) => [
                'id' => $theme->slug,
                'name' => $theme->name,
                'settings' => $theme->settings,
            ])->values(),
            'blockTypes' => collect(BlockType::cases())->map(fn (BlockType $type) => [
                'type' => $type->value,
                'label' => $type->label(),
            ])->values(),
            'socialPlatforms' => collect(SocialPlatform::cases())->map(fn (SocialPlatform $platform) => [
                'value' => $platform->value,
                'label' => $platform->label(),
            ])->values(),
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page): JsonResponse
    {
        $data = $request->validated();

        if (($data['status'] ?? null) === PageStatus::Published->value) {
            $willHaveTitle = array_key_exists('title', $data) ? filled($data['title']) : filled($page->title);

            if (! $willHaveTitle && ! $page->blocks()->exists()) {
                return response()->json([
                    'message' => 'La página necesita un título o al menos un bloque para publicarse.',
                ], 422);
            }

            if (! $page->published_at) {
                $page->published_at = now();
            }
        }

        $page->fill($data)->save();

        return response()->json([
            'ok' => true,
            'status' => $page->status->value,
            'slug' => $page->slug,
            'publicUrl' => $this->publicUrl($page),
            'qrUrl' => $this->qrUrl($page),
            'publishedAt' => $page->published_at?->toIso8601String(),
        ]);
    }

    public function makePrimary(Request $request, Page $page): RedirectResponse
    {
        $this->authorize('update', $page);

        $page->user->pages()->update(['is_primary' => false]);
        $page->update(['is_primary' => true]);

        return back();
    }

    public function destroy(Request $request, Page $page): RedirectResponse
    {
        $this->authorize('delete', $page);

        $user = $page->user;

        if ($user->pages()->count() <= 1) {
            return back()->withErrors(['pages' => 'No podés eliminar tu único biolink.']);
        }

        $wasPrimary = $page->is_primary;
        // Borrado real: la cascada elimina bloques/social/analytics y libera el slug.
        $page->forceDelete();

        if ($wasPrimary) {
            $user->pages()->orderBy('id')->first()?->update(['is_primary' => true]);
        }

        return redirect()->route('dashboard');
    }

    private function publicUrl(Page $page): string
    {
        return $page->is_primary
            ? url('/'.$page->user->username)
            : url('/'.$page->user->username.'/'.$page->slug);
    }

    private function qrUrl(Page $page): string
    {
        return $page->is_primary
            ? route('public.qr', ['username' => $page->user->username])
            : route('public.qr.slug', ['username' => $page->user->username, 'slug' => $page->slug]);
    }

    private function uniqueSlug(User $user): string
    {
        $base = 'biolink';
        $slug = $base;
        $counter = 1;

        while ($user->pages()->where('slug', $slug)->exists()) {
            $counter++;
            $slug = "{$base}-{$counter}";
        }

        return $slug;
    }
}
