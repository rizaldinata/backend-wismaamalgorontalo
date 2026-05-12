<?php

namespace Modules\Inventory\Enums;

enum ItemCondition: string
{
    case GOOD = 'good';
    case FAIR = 'fair';
    case BROKEN = 'broken';
    case LOST = 'lost';
}
