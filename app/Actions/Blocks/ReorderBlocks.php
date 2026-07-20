<?php

namespace App\Actions\Blocks;

use App\Models\Block;
use App\Models\Page;
use Illuminate\Support\Facades\DB;

class ReorderBlocks
{
    /**
     * Reasigna `position` según el orden de public_ids recibido del cliente.
     * El backend no confía en índices arbitrarios: recalcula a partir de la
     * lista de IDs y lo hace de forma transaccional.
     *
     * @param  array<int, string>  $publicIds
     */
    public function handle(Page $page, array $publicIds): void
    {
        DB::transaction(function () use ($page, $publicIds) {
            $idByPublicId = $page->blocks()->pluck('id', 'public_id');

            foreach (array_values($publicIds) as $index => $publicId) {
                if (isset($idByPublicId[$publicId])) {
                    Block::query()
                        ->where('id', $idByPublicId[$publicId])
                        ->update(['position' => $index + 1]);
                }
            }
        });
    }
}
