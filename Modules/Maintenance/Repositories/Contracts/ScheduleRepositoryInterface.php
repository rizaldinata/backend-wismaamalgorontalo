<?php

namespace Modules\Maintenance\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Maintenance\Models\MaintenanceSchedule;
use Modules\Maintenance\Models\MaintenanceScheduleUpdate;

interface ScheduleRepositoryInterface
{
    public function getAll(): Collection;

    public function getPaginated(int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?MaintenanceSchedule;

    public function create(array $data): MaintenanceSchedule;

    public function update(MaintenanceSchedule $schedule, array $data): MaintenanceSchedule;

    public function delete(int $id);

    public function addUpdate(MaintenanceSchedule $schedule, array $data): MaintenanceScheduleUpdate;
}
