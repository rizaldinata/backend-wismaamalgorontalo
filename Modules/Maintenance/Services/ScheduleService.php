<?php

namespace Modules\Maintenance\Services;

use Modules\Maintenance\Models\MaintenanceSchedule;
use Modules\Maintenance\Repositories\Contracts\ScheduleRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScheduleService
{
    public function __construct(
        private readonly ScheduleRepositoryInterface $scheduleRepository
    ) {}

    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->scheduleRepository->getAll();
    }

    public function findById(int $id): MaintenanceSchedule
    {
        $schedule = $this->scheduleRepository->findById($id);

        if (!$schedule) {
            throw new NotFoundHttpException('Jadwal tidak ditemukan.');
        }

        return $schedule;
    }

    public function create(int $userId, array $data): MaintenanceSchedule
    {
        $data['created_by'] = $userId;
        $schedule = $this->scheduleRepository->create($data);

        // Add initial update
        $this->scheduleRepository->addUpdate($schedule, [
            'user_id' => $userId,
            'status' => $schedule->status,
            'notes' => 'Jadwal pemeliharaan telah dibuat.',
        ]);

        return $schedule;
    }

    public function update(int $id, array $data): MaintenanceSchedule
    {
        $schedule = $this->findById($id);
        return $this->scheduleRepository->update($schedule, $data);
    }

    public function delete(int $id): bool
    {
        return $this->scheduleRepository->delete($id);
    }

    public function addUpdate(int $userId, int $id, array $data): \Modules\Maintenance\Models\MaintenanceScheduleUpdate
    {
        $schedule = $this->findById($id);

        $update = $this->scheduleRepository->addUpdate($schedule, [
            'user_id' => $userId,
            'status' => $data['status'] ?? null,
            'notes' => $data['notes'],
        ]);

        if (isset($data['status'])) {
            $this->scheduleRepository->update($schedule, ['status' => $data['status']]);
        }

        return $update->load('user');
    }
}
