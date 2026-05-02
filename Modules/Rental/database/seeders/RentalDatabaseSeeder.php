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
        $rooms = Room::orderBy('id')->get();

        if ($residents->count() < 10 || $rooms->count() < 10) {
            return;
        }

        // 1. Buat 7 Penghuni Aktif (Sudah masuk kamar)
        for ($i = 0; $i < 7; $i++) {
            $room = $rooms[$i];
            $room->update(['status' => 'occupied']);
            
            // Buat 2 diantaranya sewa harian
            $rentalType = $i < 2 ? RentalType::DAILY->value : RentalType::MONTHLY->value;
            $startDate = Carbon::now()->subMonths(rand(0, 5))->subDays(rand(1, 20));
            $endDate = $rentalType === RentalType::DAILY->value 
                ? (clone $startDate)->addDays(rand(1, 7))
                : (clone $startDate)->addMonths(rand(1, 6));

            Lease::create([
                'resident_id' => $residents[$i]->id,
                'room_id' => $room->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'rental_type' => $rentalType,
                'status' => LeaseStatus::ACTIVE->value,
            ]);
        }

        // 2. Buat 4 Penghuni Pending (Masih keep, belum bayar)
        for ($i = 7; $i < 11; $i++) {
            $room = $rooms[$i];
            // Kamar yang di keep biasanya masih 'available' atau 'occupied'. 
            // Kita set 'available' agar tetap terbaca sebagai Kamar Tersedia di stat, atau biarkan.
            $room->update(['status' => 'available']);
            
            Lease::create([
                'resident_id' => $residents[$i]->id,
                'room_id' => $room->id,
                'start_date' => Carbon::now()->addDays(rand(1, 5)),
                'end_date' => Carbon::now()->addMonths(6),
                'rental_type' => RentalType::MONTHLY->value,
                'status' => LeaseStatus::PENDING->value,
            ]);
        }

        // 3. Sisanya pastikan status kamar 'available' atau 'maintenance'
        for ($i = 11; $i < count($rooms); $i++) {
            if ($i == count($rooms) - 1) {
                $rooms[$i]->update(['status' => 'maintenance']);
            } else {
                $rooms[$i]->update(['status' => 'available']);
            }
        }
    }
}
