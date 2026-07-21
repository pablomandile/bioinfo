<?php

namespace App\Blocks;

use App\Enums\BlockType;
use App\Rules\LinkUrl;

/**
 * Espejo backend del registry de bloques del frontend. Define, por tipo, las
 * reglas de validación del payload `data` y sus datos por defecto al crear.
 *
 * Añadir un tipo de bloque = añadir su caso aquí + su carpeta en
 * resources/js/blocks/<tipo>/ (ver ARCHITECTURE.md §6).
 */
class BlockTypeRegistry
{
    /**
     * Reglas de validación del payload `data.*` según el tipo.
     *
     * @return array<string, mixed>
     */
    public static function rules(BlockType $type): array
    {
        return match ($type) {
            BlockType::Link => [
                'data.label' => ['required', 'string', 'max:120'],
                'data.url' => ['required', 'string', 'max:2048', new LinkUrl],
            ],
            BlockType::Heading => [
                'data.text' => ['required', 'string', 'max:120'],
            ],
            BlockType::Text => [
                'data.text' => ['required', 'string', 'max:5000'],
            ],
            BlockType::Image => [
                'data.url' => ['required', 'url', 'max:2048'],
                'data.alt' => ['nullable', 'string', 'max:255'],
                'data.href' => ['nullable', 'url', 'max:2048'],
            ],
            BlockType::Embed => [
                'data.provider' => ['required', 'string', 'in:youtube,spotify,tiktok'],
                'data.id' => ['nullable', 'string', 'max:255'],
                'data.url' => ['nullable', 'url', 'max:2048'],
                'data.embedType' => ['nullable', 'string', 'max:20'],
            ],
            BlockType::Divider => [],
        };
    }

    /**
     * Datos iniciales del bloque al crearlo.
     *
     * @return array<string, mixed>
     */
    public static function defaultData(BlockType $type): array
    {
        return match ($type) {
            BlockType::Link => ['label' => 'Nuevo enlace', 'url' => ''],
            BlockType::Heading => ['text' => 'Encabezado'],
            BlockType::Text => ['text' => 'Escribe algo…'],
            BlockType::Image => ['url' => '', 'alt' => ''],
            BlockType::Embed => ['provider' => 'youtube', 'id' => ''],
            BlockType::Divider => [],
        };
    }
}
