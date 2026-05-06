<?php

namespace Modules\Maintenance\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Maintenance\Models\MaintenanceRequest;
use Modules\Maintenance\Models\MaintenanceRequestUpdate;
use Modules\Resident\Models\Resident;
use Modules\Maintenance\Enums\MaintenanceStatus;
use Carbon\Carbon;

class MaintenanceRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $residents = Resident::all();
        $admin = User::whereHas('roles', function ($q) {
            $q->where('name', 'super-admin')->orWhere('name', 'admin');
        })->first();

        if ($residents->isEmpty() || !$admin) {
            return;
        }

        $scenarios = [
            [
                'title' => 'Keran Air Bocor di Kamar Mandi',
                'description' => 'Keran air di wastafel kamar mandi terus menetes meskipun sudah diputar kencang. Mohon segera diperbaiki agar tidak membuang air.',
                'status' => MaintenanceStatus::PENDING,
                'days_ago' => 2,
            ],
            [
                'title' => 'Lampu Kamar Redup dan Berkedip',
                'description' => 'Lampu utama di dalam kamar mulai meredup dan sering berkedip-kedip, sangat mengganggu saat malam hari.',
                'status' => MaintenanceStatus::IN_PROGRESS,
                'days_ago' => 5,
                'update' => 'Tukang sedang dalam perjalanan, estimasi sampai jam 2 siang.',
            ],
            [
                'title' => 'AC Tidak Dingin',
                'description' => 'AC di kamar hanya mengeluarkan angin, tidak dingin seperti biasanya. Mungkin perlu tambah freon atau cuci AC.',
                'status' => MaintenanceStatus::COMPLETED,
                'days_ago' => 10,
                'update' => 'AC sudah dicuci dan freon sudah diisi ulang. Sekarang sudah dingin kembali.',
            ],
            [
                'title' => 'Pintu Pagar Bunyi Nyaring',
                'description' => 'Engsel pintu pagar depan bunyi sangat nyaring saat dibuka/ditutup.',
                'status' => MaintenanceStatus::CANCELLED,
                'days_ago' => 1,
                'update' => 'Sudah diperbaiki secara mandiri oleh penjaga malam.',
            ],
            [
                'title' => 'Saluran Air Wastafel Tersumbat',
                'description' => 'Air di wastafel mengalir sangat lambat, sepertinya ada sumbatan di pipa pembuangan.',
                'status' => MaintenanceStatus::PENDING,
                'days_ago' => 3,
            ],
            [
                'title' => 'Stop Kontak Longgar',
                'description' => 'Stop kontak di sebelah tempat tidur goyang/longgar saat mencolokkan charger, takut terjadi korsleting.',
                'status' => MaintenanceStatus::IN_PROGRESS,
                'days_ago' => 4,
                'update' => 'Teknisi internal sedang mengecek ketersediaan material pengganti.',
            ],
            [
                'title' => 'Gagang Pintu Kamar Mandi Rusak',
                'description' => 'Gagang pintu kamar mandi sulit diputar dan sering macet, hampir terkunci di dalam tadi pagi.',
                'status' => MaintenanceStatus::PENDING,
                'days_ago' => 1,
            ],
            [
                'title' => 'Jendela Tidak Bisa Dikunci',
                'description' => 'Grendel jendela rusak sehingga tidak bisa dikunci rapat, mohon segera diperbaiki demi keamanan.',
                'status' => MaintenanceStatus::COMPLETED,
                'days_ago' => 15,
                'update' => 'Grendel jendela sudah diganti dengan yang baru.',
            ],
            [
                'title' => 'Remot AC Hilang/Rusak',
                'description' => 'Remot AC tidak berfungsi meskipun baterai sudah diganti baru.',
                'status' => MaintenanceStatus::PENDING,
                'days_ago' => 6,
            ],
            [
                'title' => 'Dinding Kamar Lembab dan Berjamur',
                'description' => 'Ada rembesan air dari plafon yang membuat dinding lembab dan mulai tumbuh jamur hitam.',
                'status' => MaintenanceStatus::IN_PROGRESS,
                'days_ago' => 8,
                'update' => 'Sudah dicek ke lantai atas, ditemukan kebocoran pada pipa air. Sedang dikoordinasikan untuk perbaikan pipa.',
            ],
        ];

        foreach ($scenarios as $index => $data) {
            $resident = $residents[$index % $residents->count()];
            $roomId = $resident->activeLease?->room_id;

            if (!$roomId) continue;

            $request = MaintenanceRequest::create([
                'resident_id' => $resident->id,
                'room_id' => $roomId,
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => $data['status'],
                'reported_at' => Carbon::now()->subDays($data['days_ago']),
            ]);

            if (isset($data['update'])) {
                MaintenanceRequestUpdate::create([
                    'maintenance_request_id' => $request->id,
                    'user_id' => $admin->id,
                    'status' => $data['status'],
                    'description' => $data['update'],
                ]);
            }
        }
    }
}
