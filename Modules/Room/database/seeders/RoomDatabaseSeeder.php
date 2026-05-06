<?php

namespace Modules\Room\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Room\Models\Room;
use Modules\Room\Models\RoomImage;

class RoomDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roomTypes = [
            'standard' => [
                'titles' => ['Standard Deluxe', 'Garden View Standard', 'Budget Standard', 'Access Standard', 'Cozy Standard'],
                'price' => 500000,
                'price_daily' => 50000,
                'facilities' => ['AC', 'WiFi', 'Kasur Single', 'Lemari', 'Meja Belajar'],
            ],
            'deluxe' => [
                'titles' => ['Executive Deluxe', 'Modern Deluxe', 'Bright Deluxe', 'Smart Deluxe', 'Prime Deluxe'],
                'price' => 750000,
                'price_daily' => 75000,
                'facilities' => ['AC', 'WiFi', 'Kasur Queen', 'Lemari', 'Meja Belajar', 'Kamar Mandi Dalam', 'TV'],
            ],
            'suite' => [
                'titles' => ['Royal Suite', 'Penthouse Suite', 'Family Suite', 'Presidential Suite', 'Business Suite'],
                'price' => 1200000,
                'price_daily' => 120000,
                'facilities' => ['AC', 'WiFi', 'Kasur King', 'Lemari Besar', 'Meja Kerja', 'Kamar Mandi Dalam', 'Balkon', 'TV', 'Kulkas', 'Dapur Kecil', 'Sofa'],
            ],
        ];

        $floors = [1, 2, 3, 4];
        $roomsPerFloor = 5;

        foreach ($floors as $floor) {
            for ($i = 1; $i <= $roomsPerFloor; $i++) {
                $roomNumber = $floor . '0' . $i;

                // Determine type based on floor
                if ($floor <= 2) {
                    $typeKey = 'standard';
                } elseif ($floor == 3) {
                    $typeKey = 'deluxe';
                } else {
                    $typeKey = 'suite';
                }

                $typeData = $roomTypes[$typeKey];
                $title = $typeData['titles'][array_rand($typeData['titles'])] . ' ' . $roomNumber;

                $statuses = ['available', 'occupied', 'maintenance'];
                $status = $statuses[array_rand($statuses)];

                // Make sure we have a good mix
                if ($roomNumber == '101' || $roomNumber == '201')
                    $status = 'occupied';
                if ($roomNumber == '103')
                    $status = 'maintenance';

                $room = Room::create([
                    'number' => $roomNumber,
                    'title' => $title,
                    'price' => $typeData['price'],
                    'price_daily' => $typeData['price_daily'],
                    'status' => $status,
                    'description' => "Kamar {$title} yang dirancang khusus untuk kenyamanan maksimal Anda. Menawarkan suasana tenang dengan pencahayaan alami yang cukup. Dilengkapi dengan fasilitas modern, area kerja yang ergonomis, dan koneksi internet cepat, menjadikannya pilihan ideal baik bagi wisatawan maupun profesional.",
                    'facilities' => $typeData['facilities'],
                ]);

                $imagesCount = rand(2, 5);
                for ($j = 0; $j < $imagesCount; $j++) {
                    RoomImage::create([
                        'room_id' => $room->id,
                        'image_path' => 'rooms/dummy-room-' . $room->number . '-' . ($j + 1) . '.jpg',
                        'order' => $j,
                    ]);
                }
            }
        }

        $this->command->info('Berhasil membuat 20 data room dengan facilities dan images!');
    }
}
