<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Models\AnalyticsDaily;
use App\Models\Block;
use App\Models\Page;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function show(Page $page): Response
    {
        $this->authorize('update', $page);

        $daily = AnalyticsDaily::query()->where('page_id', $page->id)->get();

        $views = (int) $daily->where('type', EventType::PageView)->sum('count');
        $clicks = (int) $daily->where('type', EventType::LinkClick)->sum('count');

        $series = collect(range(29, 0))->map(function (int $daysAgo) use ($daily) {
            $date = Carbon::now()->subDays($daysAgo)->toDateString();
            $forDate = $daily->filter(fn ($row) => $row->date->toDateString() === $date);

            return [
                'date' => $date,
                'views' => (int) $forDate->where('type', EventType::PageView)->sum('count'),
                'clicks' => (int) $forDate->where('type', EventType::LinkClick)->sum('count'),
            ];
        })->values();

        $clicksByBlock = $daily
            ->filter(fn ($row) => $row->type === EventType::LinkClick && $row->block_id !== null)
            ->groupBy('block_id')
            ->map(fn ($rows) => (int) $rows->sum('count'));

        $blocks = Block::query()->whereIn('id', $clicksByBlock->keys())->get()->keyBy('id');

        $topBlocks = $clicksByBlock
            ->map(function (int $count, int $blockId) use ($blocks) {
                $block = $blocks->get($blockId);
                $data = $block?->data ?? [];

                return [
                    'label' => (string) ($data['label'] ?? $data['text'] ?? $data['url'] ?? ($block?->type->value ?? 'Bloque')),
                    'clicks' => $count,
                ];
            })
            ->sortByDesc('clicks')
            ->take(10)
            ->values();

        return Inertia::render('analytics/Show', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title ?: $page->user->username,
                'editUrl' => route('pages.edit', $page),
            ],
            'totals' => [
                'views' => $views,
                'clicks' => $clicks,
                'ctr' => $views > 0 ? round(($clicks / $views) * 100, 1) : 0,
            ],
            'series' => $series,
            'topBlocks' => $topBlocks,
        ]);
    }
}
