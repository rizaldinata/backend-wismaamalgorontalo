<?php

namespace Modules\Resident\Services;

use Modules\Room\Models\Room;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Resident\Enums\LeaseStatus;
use Modules\Resident\Models\Lease;
use Modules\Room\Enums\RoomStatus;

class LeaseService
{
    public function createLease($user, Room $room, array $data): Lease
    {
        return DB::transaction(function () use ($user, $room, $data) {
            $endDate = Carbon::parse($data['start_date'])
                ->addMonths($data['duration_months']);

            $totalPrice = $room->price * $data['duration_months'];

            $lease = Lease::create([
                'user_id' => $user->id,
                'room_id' => $room->id,
                'start_date' => $data['start_date'],
                'end_date' => $endDate,
                'status' => LeaseStatus::PENDING,
                'total_price' => $totalPrice,
                'price_per_month' => $room->price,
            ]);

            $room->update(['status' => RoomStatus::OCCUPIED]);

            return $lease;
        });
    }

    public function updateStatus(Lease $lease, string $newStatus)
    {
        return DB::transaction(function () use ($lease, $newStatus) {
            $lease->update(['status' => $newStatus]);

            $room = $lease->room;

            $statusEnum = LeaseStatus::tryFrom($newStatus);

            if ($statusEnum === LeaseStatus::ACTIVE) {
                $room->update(['status' => RoomStatus::OCCUPIED]);
            } elseif (in_array($statusEnum, [LeaseStatus::FINISHED, LeaseStatus::CANCELLED, LeaseStatus::REJECTED])) {
                $room->update(['status' => RoomStatus::AVAILABLE]);
            }

            return $lease;
        });
    }
}
