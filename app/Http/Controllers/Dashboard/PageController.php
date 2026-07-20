<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\BlockType;
use App\Enums\PageStatus;
use App\Enums\SocialPlatform;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Page;
use App\Models\SocialLink;
use App\Models\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function edit(Request $request, Page $page): Response
    {
        $this->authorize('update', $page);

        $page->load(['blocks', 'socialLinks', 'user']);

        return Inertia::render('editor/Edit', [
            'page' => [
                'id' => $page->id,
                'username' => $page->user->username,
                'slug' => $page->slug,
                'title' => $page->title,
                'bio' => $page->bio,
                'avatarUrl' => $page->getFirstMediaUrl('avatar') ?: null,
                'layout' => $page->layout->value,
                'status' => $page->status->value,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'theme' => $page->theme ?? ['presetId' => null, 'mode' => 'light', 'tokens' => []],
                'publicUrl' => url('/'.$page->user->username),
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
            'publishedAt' => $page->published_at?->toIso8601String(),
        ]);
    }
}
