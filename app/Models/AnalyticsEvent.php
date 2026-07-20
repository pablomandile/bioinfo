<?php

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    /** @use HasFactory<\Database\Factories\AnalyticsEventFactory> */
    use HasFactory;

    /**
     * La tabla solo registra el momento de creación del evento.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'page_id',
        'block_id',
        'type',
        'ip_hash',
        'country_code',
        'device_type',
        'browser',
        'os',
        'referrer_host',
        'utm',
        'target_url',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'utm' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** @return BelongsTo<Block, $this> */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }
}
