<?php

namespace App\Jobs;

use App\Analytics\VisitorContext;
use App\Enums\EventType;
use App\Models\AnalyticsEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RecordPageViewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $pageId,
        public ?string $ip,
        public ?string $userAgent,
        public ?string $referer,
    ) {}

    public function handle(): void
    {
        $ipHash = VisitorContext::ipHash($this->ip);
        $cacheKey = "pv:{$this->pageId}:{$ipHash}";

        // Deduplicación de vistas por visitante dentro de una ventana de 30 min.
        if ($ipHash && Cache::has($cacheKey)) {
            return;
        }

        AnalyticsEvent::create([
            'page_id' => $this->pageId,
            'type' => EventType::PageView->value,
            'ip_hash' => $ipHash,
            'device_type' => VisitorContext::deviceType($this->userAgent),
            'referrer_host' => VisitorContext::referrerHost($this->referer),
            'created_at' => now(),
        ]);

        if ($ipHash) {
            Cache::put($cacheKey, true, now()->addMinutes(30));
        }
    }
}
