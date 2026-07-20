<?php

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsDaily extends Model
{
    /** @use HasFactory<\Database\Factories\AnalyticsDailyFactory> */
    use HasFactory;

    protected $table = 'analytics_daily';

    public $timestamps = false;

    protected $fillable = [
        'page_id',
        'block_id',
        'type',
        'date',
        'count',
    ];

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'date' => 'date',
            'count' => 'integer',
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
