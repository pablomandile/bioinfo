<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

class QrService
{
    public function svg(string $data): string
    {
        return (new Builder(
            writer: new SvgWriter(),
            data: $data,
            size: 300,
            margin: 8,
        ))->build()->getString();
    }
}
