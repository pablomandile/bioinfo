<?php

namespace App\Enums;

enum PageLayout: string
{
    case List = 'list';
    case Grid = 'grid';

    public function label(): string
    {
        return match ($this) {
            self::List => 'Lista clásica',
            self::Grid => 'Grid (Bento)',
        };
    }
}
