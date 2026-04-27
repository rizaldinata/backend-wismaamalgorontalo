<?php

namespace Modules\Maintenance\Enums;

enum ScheduleType: string
{
    case PEMBERSIHAN = 'pembersihan';
    case PERAWATAN   = 'perawatan';
}
