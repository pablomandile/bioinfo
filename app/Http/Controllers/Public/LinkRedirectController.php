<?php

namespace App\Http\Controllers\Public;

use App\Enums\BlockType;
use App\Http\Controllers\Controller;
use App\Jobs\RecordLinkClickJob;
use App\Models\Block;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LinkRedirectController extends Controller
{
    /**
     * Redirige un clic a través de /go/{block} y (en la Fase 1.3) registra el evento.
     */
    public function __invoke(Request $request, Block $block): RedirectResponse
    {
        abort_unless($block->type === BlockType::Link, 404);

        $url = $block->data['url'] ?? null;

        abort_unless(is_string($url) && $url !== '', 404);

        if ($block->is_visible) {
            RecordLinkClickJob::dispatch(
                $block->page_id,
                $block->id,
                $url,
                $request->ip(),
                $request->userAgent(),
                $request->headers->get('referer'),
            );
        }

        return redirect()->away($url);
    }
}
