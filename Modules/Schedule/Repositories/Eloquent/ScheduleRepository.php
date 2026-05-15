<?php

namespace Modules\Schedule\Repositories\Eloquent;

use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Models\Schedule;
use Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScheduleRepository implements ScheduleRepositoryInterface
{
    public function create(array $data): Schedule
    {
        return Schedule::create($data);
    }

    public function findById(int $id): Schedule
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            throw new NotFoundHttpException('Jadwal tidak ditemukan.');
        }

        return $schedule;
    }

    public function updateStatus(Schedule $schedule, string $status, array $extra = []): Schedule
    {
        $schedule->update(array_merge(['status' => $status], $extra));

        return $schedule->fresh();
    }

    public function getByRoomId(int $roomId): iterable
    {
        return Schedule::where('room_id', $roomId)
            ->orderByDesc('start_date')
            ->get();
    }

    public function getActiveByRoomId(int $roomId): ?Schedule
    {
        return Schedule::where('room_id', $roomId)
            ->where('status', ScheduleStatus::ACTIVE->value)
            ->first();
    }

    public function getByTenantUserId(int $userId): iterable
    {
        return Schedule::where('tenant_user_id', $userId)
            ->orderByDesc('start_date')
            ->get();
    }

    public function getActiveByTenantUserId(int $userId): ?Schedule
    {
        return Schedule::where('tenant_user_id', $userId)
            ->where('status', ScheduleStatus::ACTIVE->value)
            ->first();
    }

    public function getAllPaginated(array $filters = []): mixed
    {
        $query = Schedule::query();

        if (!empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('start_date')->paginate(15);
    }
}
