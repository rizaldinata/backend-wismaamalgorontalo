<?php

namespace Modules\Notification\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Notification\Contracts\NotificationRepositoryInterface;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Models\NotificationLog;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function logNotification(NotificationType $type, string $target, string $message, string $status, ?string $error = null): NotificationLog
    {
        return NotificationLog::create([
            'type' => $type->value,
            'target_phone' => $target,
            'message_body' => $message,
            'status' => $status,
            'error_response' => $error,
        ]);
    }

    public function getLogsPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return NotificationLog::latest()->paginate($perPage);
    }

    public function findById(int $id): ?NotificationLog
    {
        return NotificationLog::findOrFail($id);
    }

    public function updateStatus(NotificationLog $log, string $status, ?string $error = null): bool
    {
        return $log->update([
            'status' => $status,
            'error_response' => $error,
        ]);
    }
}