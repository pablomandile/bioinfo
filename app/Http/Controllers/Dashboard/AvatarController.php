<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AvatarController extends Controller
{
    public function store(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $request->validate([
            'avatar' => ['required', 'image', 'max:5120'],
        ]);

        $page->clearMediaCollection('avatar');
        $page->addMediaFromRequest('avatar')->toMediaCollection('avatar');

        return response()->json([
            'avatarUrl' => $page->getFirstMediaUrl('avatar') ?: null,
        ]);
    }

    public function destroy(Page $page): Response
    {
        $this->authorize('update', $page);

        $page->clearMediaCollection('avatar');

        return response()->noContent();
    }
}
