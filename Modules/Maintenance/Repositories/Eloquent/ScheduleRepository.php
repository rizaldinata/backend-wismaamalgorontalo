<?php

namespace Modules\Maintenance\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Modules\Maintenance\Models\MaintenanceSchedule;
use Modules\Maintenance\Models\MaintenanceScheduleUpdate;
use Modules\Maintenance\Repositories\Contracts\ScheduleRepositoryInterface;

class ScheduleRepository implements ScheduleRepositoryInterface
{
    public function getAll(): Collection
    {
        return MaintenanceSchedule::latest('start_time')->get();
    }

    public function findById(int $id): ?MaintenanceSchedule
    {
        return MaintenanceSchedule::with(['updates.user'])->find($id);
    }

    public function create(array $data): MaintenanceSchedule
    {
        return MaintenanceSchedule::create($data);
    }

    public function update(MaintenanceSchedule $schedule, array $data): MaintenanceSchedule
    {
        $schedule->update($data);
        return $schedule->fresh();
    }

    public function delete(int $id): bool
    {
        return MaintenanceSchedule::destroy($id) > 0;
    }

    public function addUpdate(MaintenanceSchedule $schedule, array $data): MaintenanceScheduleUpdate
    {
        return $schedule->updates()->create($data);
    }
}
