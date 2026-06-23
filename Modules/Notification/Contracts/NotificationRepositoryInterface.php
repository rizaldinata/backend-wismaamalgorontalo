<?php

namespace Modules\Notification\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Models\NotificationLog;

interface NotificationRepositoryInterface
{
    public function logNotification(NotificationType $type, string $target, string $message, string $status, ?string $error = null): NotificationLog;

    public function getLogsPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?NotificationLog;

    public function updateStatus(NotificationLog $log, string $status, ?string $error = null): bool;

    public function getSummary(): array;

    public function getRecipients(): Collection;
    public function markAllAsRead(): int;
    public function countUnread(): int;
}
