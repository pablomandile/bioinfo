<?php

namespace App\Http\Controllers\Public;

use App\Enums\BlockType;
use App\Http\Controllers\Controller;
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

        // TODO(Fase 1.3): dispatch RecordLinkClickJob para registrar la analítica del clic.

        return redirect()->away($url);
    }
}
