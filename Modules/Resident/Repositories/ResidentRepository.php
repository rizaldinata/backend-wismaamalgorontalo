<?php

namespace Modules\Resident\Repositories;

use Modules\Resident\Models\Resident;
use Modules\Resident\Repositories\Contracts\ResidentRepositoryInterface;

class ResidentRepository implements ResidentRepositoryInterface
{
    public function findByUserId(int $userId): ?Resident
    {
        return Resident::where('user_id', $userId)->first();
    }

    public function updateOrCreateByUserId(int $userId, array $data): Resident
    {
        return Resident::updateOrCreate(
            ['user_id' => $userId],
            $data
        );
    }
}
