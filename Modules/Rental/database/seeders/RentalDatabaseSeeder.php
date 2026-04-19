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
        $residents = Resident::all();
        $occupiedRooms = Room::where('status', 'occupied')->get();
        $availableRooms = Room::where('status', 'available')->take(3)->get();

        if ($residents->isEmpty() || $occupiedRooms->isEmpty()) {
            return;
        }

        // Create ACTIVE leases for occupied rooms
        foreach ($occupiedRooms as $index => $room) {
            $resident = $residents[$index % $residents->count()];
            
            Lease::updateOrCreate(
                [
                    'resident_id' => $resident->id,
                    'room_id' => $room->id
                ],
                [
                    'start_date' => Carbon::now()->subMonths(rand(1, 3)),
                    'end_date' => Carbon::now()->addMonths(rand(3, 9)),
                    'rental_type' => RentalType::MONTHLY->value,
                    'status' => LeaseStatus::ACTIVE->value,
                ]
            );
        }

        // Create some PENDING leases for available rooms
        foreach ($availableRooms as $index => $room) {
            $resident = $residents[($index + 5) % $residents->count()];
            
            Lease::create([
                'resident_id' => $resident->id,
                'room_id' => $room->id,
                'start_date' => Carbon::now()->addDays(rand(1, 7)),
                'end_date' => Carbon::now()->addMonths(6),
                'rental_type' => RentalType::MONTHLY->value,
                'status' => LeaseStatus::PENDING->value,
            ]);
        }
    }
}
