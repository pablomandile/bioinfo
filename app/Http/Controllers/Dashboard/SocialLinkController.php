<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\SocialPlatform;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SocialLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class SocialLinkController extends Controller
{
    public function store(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $validated = $request->validate([
            'platform' => ['required', Rule::enum(SocialPlatform::class)],
            'url' => ['required', 'url', 'max:2048'],
        ]);

        $link = $page->socialLinks()->create([
            'platform' => $validated['platform'],
            'url' => $validated['url'],
            'position' => ($page->socialLinks()->max('position') ?? 0) + 1,
        ]);

        return response()->json($this->present($link), 201);
    }

    public function update(Request $request, Page $page, SocialLink $socialLink): JsonResponse
    {
        $this->authorize('update', $socialLink);

        $validated = $request->validate([
            'platform' => ['sometimes', Rule::enum(SocialPlatform::class)],
            'url' => ['sometimes', 'url', 'max:2048'],
        ]);

        $socialLink->update($validated);

        return response()->json($this->present($socialLink));
    }

    public function destroy(Page $page, SocialLink $socialLink): Response
    {
        $this->authorize('delete', $socialLink);

        $socialLink->delete();

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SocialLink $link): array
    {
        return [
            'id' => $link->id,
            'platform' => $link->platform->value,
            'label' => $link->platform->label(),
            'url' => $link->url,
        ];
    }
}
