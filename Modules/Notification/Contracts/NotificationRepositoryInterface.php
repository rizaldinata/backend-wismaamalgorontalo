<?php

namespace Modules\Notification\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Models\NotificationLog;

interface NotificationRepositoryInterface
{
    public function logNotification(NotificationType $type, string $target, string $message, string $status, ?string $error = null): NotificationLog;
    public function getLogsPaginated(int $perPage = 15): LengthAwarePaginator;
    public function findById(int $id): ?NotificationLog;
    public function updateStatus(NotificationLog $log, string $status, ?string $error = null): bool;
}