<?php

namespace App\Models;

use App\Enums\BlockSize;
use App\Enums\BlockType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Block extends Model
{
    /** @use HasFactory<\Database\Factories\BlockFactory> */
    use HasFactory;

    protected $fillable = [
        'page_id',
        'parent_id',
        'type',
        'data',
        'position',
        'size',
        'grid_col_span',
        'grid_row_span',
        'is_visible',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => BlockType::class,
            'size' => BlockSize::class,
            'data' => 'array',
            'position' => 'integer',
            'grid_col_span' => 'integer',
            'grid_row_span' => 'integer',
            'is_visible' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Block $block) {
            if (empty($block->public_id)) {
                $block->public_id = (string) Str::ulid();
            }
        });
    }

    /**
     * Bloques visibles públicamente (activos y dentro de su ventana de programación).
     *
     * @param  Builder<Block>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $now = now();

        $query->where('is_visible', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** @return BelongsTo<Block, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Block::class, 'parent_id');
    }

    /** @return HasMany<Block, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Block::class, 'parent_id')->orderBy('position');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
