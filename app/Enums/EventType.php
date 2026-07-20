<?php

namespace App\Enums;

enum EventType: string
{
    case PageView = 'page_view';
    case LinkClick = 'link_click';
}
