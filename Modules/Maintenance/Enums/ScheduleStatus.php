<?php

namespace Modules\Maintenance\Enums;

enum ScheduleStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case DONE        = 'done';
    case CANCELLED   = 'cancelled';
}
