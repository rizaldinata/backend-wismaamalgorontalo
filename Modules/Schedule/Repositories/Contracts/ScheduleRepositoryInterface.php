<?php

namespace Modules\Schedule\Repositories\Contracts;

use Modules\Schedule\Models\Schedule;

interface ScheduleRepositoryInterface
{
    public function create(array $data): Schedule;

    public function findById(int $id): Schedule;

    public function updateStatus(Schedule $schedule, string $status, array $extra = []): Schedule;

    public function getByRoomId(int $roomId): iterable;

    public function getActiveByRoomId(int $roomId): ?Schedule;

    public function getByTenantUserId(int $userId): iterable;

    public function getActiveByTenantUserId(int $userId): ?Schedule;

    public function getAllPaginated(array $filters = []): mixed;
}
