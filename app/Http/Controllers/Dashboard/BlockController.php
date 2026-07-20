<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\Blocks\ReorderBlocks;
use App\Blocks\BlockTypeRegistry;
use App\Enums\BlockType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderBlocksRequest;
use App\Http\Requests\StoreBlockRequest;
use App\Http\Requests\UpdateBlockRequest;
use App\Models\Block;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BlockController extends Controller
{
    public function store(StoreBlockRequest $request, Page $page): JsonResponse
    {
        $type = BlockType::from($request->validated('type'));

        $block = $page->blocks()->create([
            'type' => $type->value,
            'data' => BlockTypeRegistry::defaultData($type),
            'position' => ($page->blocks()->max('position') ?? 0) + 1,
        ]);

        return response()->json($block->toPublicArray(), 201);
    }

    public function update(UpdateBlockRequest $request, Page $page, Block $block): JsonResponse
    {
        $validated = $request->validated();

        $payload = [];

        if (array_key_exists('data', $validated)) {
            $payload['data'] = $validated['data'];
        }

        if (array_key_exists('size', $validated)) {
            $payload['size'] = $validated['size'];
        }

        if (array_key_exists('isVisible', $validated)) {
            $payload['is_visible'] = $validated['isVisible'];
        }

        $block->update($payload);

        return response()->json($block->fresh()->toPublicArray());
    }

    public function destroy(Page $page, Block $block): Response
    {
        $this->authorize('delete', $block);

        $block->delete();

        return response()->noContent();
    }

    public function reorder(ReorderBlocksRequest $request, Page $page, ReorderBlocks $reorder): Response
    {
        $reorder->handle($page, $request->validated('ids'));

        return response()->noContent();
    }
}
