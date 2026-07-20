<?php

namespace App\Enums;

enum BlockSize: string
{
    case Sm = 'sm';
    case Md = 'md';
    case Lg = 'lg';

    /**
     * Número de columnas que ocupa la card en el layout grid (Bento).
     */
    public function columnSpan(): int
    {
        return match ($this) {
            self::Sm => 1,
            self::Md => 1,
            self::Lg => 2,
        };
    }
}
