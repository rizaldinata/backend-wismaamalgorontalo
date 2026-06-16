<?php

namespace Modules\Schedule\Enums;

enum ScheduleStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case FINISHED = 'finished';
    case CANCELLED = 'cancelled';
}
