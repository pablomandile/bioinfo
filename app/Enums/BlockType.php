<?php

namespace App\Enums;

enum BlockType: string
{
    case Link = 'link';
    case Heading = 'heading';
    case Text = 'text';
    case Image = 'image';
    case Embed = 'embed';
    case Divider = 'divider';

    public function label(): string
    {
        return match ($this) {
            self::Link => 'Enlace',
            self::Heading => 'Encabezado',
            self::Text => 'Texto',
            self::Image => 'Imagen',
            self::Embed => 'Embed',
            self::Divider => 'Separador',
        };
    }
}
