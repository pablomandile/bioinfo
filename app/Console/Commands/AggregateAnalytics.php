<?php

namespace App\Console\Commands;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use Illuminate\Console\Command;

class AggregateAnalytics extends Command
{
    protected $signature = 'analytics:rollup {--days=2}';

    protected $description = 'Consolida los eventos crudos de analítica en agregados diarios';

    public function handle(): int
    {
        $since = now()->subDays((int) $this->option('days'))->startOfDay();

        $rows = AnalyticsEvent::query()
            ->selectRaw('page_id, block_id, type, DATE(created_at) as day, COUNT(*) as total')
            ->where('created_at', '>=', $since)
            ->groupBy('page_id', 'block_id', 'type', 'day')
            ->get();

        foreach ($rows as $row) {
            AnalyticsDaily::updateOrCreate(
                [
                    'page_id' => $row->page_id,
                    'block_id' => $row->block_id,
                    'type' => $row->type,
                    'date' => $row->day,
                ],
                ['count' => (int) $row->total],
            );
        }

        $this->info("Rollup completado: {$rows->count()} grupos agregados.");

        return self::SUCCESS;
    }
}
