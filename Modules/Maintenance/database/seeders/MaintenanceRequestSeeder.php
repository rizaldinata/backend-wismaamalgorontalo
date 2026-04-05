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

        // 1. Pending Request
        $req1 = MaintenanceRequest::create([
            'resident_id' => $residents[0]->id,
            'room_id' => $residents[0]->activeLease?->room_id ?? 1,
            'title' => 'Keran Air Bocor di Kamar Mandi',
            'description' => 'Keran air di wastafel kamar mandi terus menetes meskipun sudah diputar kencang. Mohon segera diperbaiki agar tidak membuang air.',
            'status' => MaintenanceStatus::PENDING,
            'reported_at' => Carbon::now()->subDays(2),
        ]);

        // 2. In Progress Request
        $req2 = MaintenanceRequest::create([
            'resident_id' => $residents[1]->id,
            'room_id' => $residents[1]->activeLease?->room_id ?? 2,
            'title' => 'Lampu Kamar Redup dan Berkedip',
            'description' => 'Lampu utama di dalam kamar mulai meredup dan sering berkedip-kedip, sangat mengganggu saat malam hari.',
            'status' => MaintenanceStatus::IN_PROGRESS,
            'reported_at' => Carbon::now()->subDays(5),
        ]);

        MaintenanceRequestUpdate::create([
            'maintenance_request_id' => $req2->id,
            'user_id' => $admin->id,
            'status' => MaintenanceStatus::IN_PROGRESS,
            'description' => 'Tukang sedang dalam perjalanan, estimasi sampai jam 2 siang.',
        ]);

        // 3. Completed Request
        $req3 = MaintenanceRequest::create([
            'resident_id' => $residents[2]->id,
            'room_id' => $residents[2]->activeLease?->room_id ?? 3,
            'title' => 'AC Tidak Dingin',
            'description' => 'AC di kamar 103 hanya mengeluarkan angin, tidak dingin seperti biasanya. Mungkin perlu tambah freon atau cuci AC.',
            'status' => MaintenanceStatus::COMPLETED,
            'reported_at' => Carbon::now()->subDays(10),
        ]);

        MaintenanceRequestUpdate::create([
            'maintenance_request_id' => $req3->id,
            'user_id' => $admin->id,
            'status' => MaintenanceStatus::IN_PROGRESS,
            'description' => 'Pengecekan awal: Freon habis. Sedang menunggu pengisian.',
        ]);

        MaintenanceRequestUpdate::create([
            'maintenance_request_id' => $req3->id,
            'user_id' => $admin->id,
            'status' => MaintenanceStatus::COMPLETED,
            'description' => 'AC sudah dicuci dan freon sudah diisi ulang. Sekarang sudah dingin kembali. Silakan dicek.',
        ]);

        // 4. Cancelled Request
        $req4 = MaintenanceRequest::create([
            'resident_id' => $residents[0]->id,
            'room_id' => null, // Area umum
            'title' => 'Pintu Pagar Bunyi Nyaring',
            'description' => 'Engsel pintu pagar depan bunyi sangat nyaring saat dibuka/ditutup.',
            'status' => MaintenanceStatus::CANCELLED,
            'reported_at' => Carbon::now()->subDays(1),
        ]);

        MaintenanceRequestUpdate::create([
            'maintenance_request_id' => $req4->id,
            'user_id' => $admin->id,
            'status' => MaintenanceStatus::CANCELLED,
            'description' => 'Sudah diperbaiki secara mandiri oleh penjaga malam dengan pemberian oli.',
        ]);
    }
}
