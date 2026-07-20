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

class RecordLinkClickJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $pageId,
        public int $blockId,
        public ?string $targetUrl,
        public ?string $ip,
        public ?string $userAgent,
        public ?string $referer,
    ) {}

    public function handle(): void
    {
        AnalyticsEvent::create([
            'page_id' => $this->pageId,
            'block_id' => $this->blockId,
            'type' => EventType::LinkClick->value,
            'ip_hash' => VisitorContext::ipHash($this->ip),
            'device_type' => VisitorContext::deviceType($this->userAgent),
            'referrer_host' => VisitorContext::referrerHost($this->referer),
            'target_url' => $this->targetUrl,
            'created_at' => now(),
        ]);
    }
}
