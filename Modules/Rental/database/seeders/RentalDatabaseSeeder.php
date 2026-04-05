<?php

namespace Modules\Rental\database\seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\Rental\Enums\LeaseStatus;
use Modules\Rental\Enums\RentalType;
use Modules\Rental\Models\Lease;
use Modules\Resident\Models\Resident;
use Modules\Room\Models\Room;

class RentalDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $resident = Resident::first();
        $room = Room::first();

        if ($resident && $room) {
            Lease::updateOrCreate(
                [
                    'resident_id' => $resident->id,
                    'room_id' => $room->id
                ],
                [
                    'start_date' => Carbon::now()->startOfMonth(),
                    'end_date' => Carbon::now()->startOfMonth()->addMonths(6),
                    'rental_type' => RentalType::MONTHLY->value,
                    'status' => LeaseStatus::PENDING->value,
                ]
            );
        }
    }
}
