<?php

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Notification\Enums\NotificationStatus;
use Modules\Notification\Enums\NotificationType;

class NotificationLog extends Model
{
    protected $fillable = [
        'type',
        'target_phone',
        'message_body',
        'status',
        'error_response',
    ];

    protected $casts = [
        'status' => NotificationStatus::class,
        'type' => NotificationType::class,
    ];
}
