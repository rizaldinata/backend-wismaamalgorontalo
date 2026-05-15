<?php

namespace Modules\Schedule\Enums;

enum ScheduleType: string
{
    case SEWA        = 'sewa';
    case MAINTENANCE = 'maintenance';
    case KEBERSIHAN  = 'kebersihan';
    case BLOKIR      = 'blokir';
}
