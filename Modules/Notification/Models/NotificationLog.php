<?php

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Notification\Enums\NotificationStatus;
use Modules\Notification\Enums\NotificationType;

class NotificationLog extends Model
{
    use HasFactory;

    protected static function newFactory(): \Modules\Notification\database\factories\NotificationLogFactory
    {
        return \Modules\Notification\database\factories\NotificationLogFactory::new();
    }

    protected $fillable = [
        'type',
        'target_phone',
        'message_body',
        'status',
        'error_response',
        'is_read',
    ];

    protected $casts = [
        'status' => NotificationStatus::class,
        'type' => NotificationType::class,
        'is_read' => 'boolean',
    ];
}
