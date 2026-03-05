<?php

namespace Modules\Resident\Repositories\Contracts;

use Modules\Resident\Models\Resident;

interface ResidentRepositoryInterface
{
    public function findByUserId(int $userId): ?Resident;
    public function updateOrCreateByUserId(int $userId, array $data): Resident;
}
