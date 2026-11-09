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

        /*
         * Por el query builder y no por el modelo, a propósito.
         *
         * `AnalyticsDaily` castea `date`, así que al guardar por el modelo
         * Eloquent escribe la fecha con hora ('2026-11-07 00:00:00') mientras
         * que el `where` de búsqueda lleva solo el día ('2026-11-07'). En MySQL
         * no se ve, porque la columna es DATE y trunca; en una conexión que no
         * valide tipos —SQLite— los dos formatos conviven, el updateOrCreate
         * no encuentra la fila que él mismo escribió e intenta insertarla de
         * nuevo, rompiendo la clave única. Como esto corre cada hora por cron,
         * tiene que ser idempotente en cualquier motor.
         */
        foreach ($rows as $row) {
            AnalyticsDaily::query()->toBase()->updateOrInsert(
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
