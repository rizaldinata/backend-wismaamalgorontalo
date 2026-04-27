<?php

namespace Modules\Maintenance\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Maintenance\Models\MaintenanceSchedule;
use Modules\Maintenance\Enums\ScheduleType;
use Modules\Maintenance\Enums\ScheduleSubtype;
use Modules\Maintenance\Enums\ScheduleStatus;
use Carbon\Carbon;

class MaintenanceScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::whereHas('roles', function ($q) {
            $q->where('name', 'super-admin')->orWhere('name', 'admin');
        })->first();

        if (!$admin) {
            return;
        }

        $schedules = [
            [
                'technician_name' => 'Budi Santoso (AC Specialist)',
                'location' => 'Lantai 1 & 2',
                'type' => ScheduleType::PERAWATAN,
                'subtype' => ScheduleSubtype::MAINTENANCE,
                'status' => ScheduleStatus::DONE,
                'notes' => 'Pembersihan filter AC dan pengecekan freon rutin bulanan.',
                'start_time' => Carbon::now()->subDays(5)->setHour(9),
                'end_time' => Carbon::now()->subDays(5)->setHour(15),
            ],
            [
                'technician_name' => 'Tim Kebersihan Berkah',
                'location' => 'Seluruh Area Umum',
                'type' => ScheduleType::PEMBERSIHAN,
                'subtype' => ScheduleSubtype::DEEP_CLEANING,
                'status' => ScheduleStatus::DONE,
                'notes' => 'Fogging area parkir dan pembersihan kaca jendela luar.',
                'start_time' => Carbon::now()->subDays(10)->setHour(8),
                'end_time' => Carbon::now()->subDays(10)->setHour(16),
            ],
            [
                'technician_name' => 'Agus Perdana',
                'location' => 'Kamar Suite 401-405',
                'type' => ScheduleType::PERAWATAN,
                'subtype' => ScheduleSubtype::RUTIN,
                'status' => ScheduleStatus::IN_PROGRESS,
                'notes' => 'Pengecekan rutin fasilitas kamar suite (TV, Kulkas, Water Heater).',
                'start_time' => Carbon::now()->addDays(1)->setHour(10),
                'end_time' => Carbon::now()->addDays(1)->setHour(14),
            ],
            [
                'technician_name' => 'Terminator Pest Control',
                'location' => 'Area Dapur & Gudang',
                'type' => ScheduleType::PEMBERSIHAN,
                'subtype' => ScheduleSubtype::RUTIN,
                'status' => ScheduleStatus::IN_PROGRESS,
                'notes' => 'Penyemprotan anti hama (kecoa & semut) rutin triwulan.',
                'start_time' => Carbon::now()->addDays(3)->setHour(13),
                'end_time' => Carbon::now()->addDays(3)->setHour(15),
            ],
            [
                'technician_name' => 'Dedi Junaedi',
                'location' => 'Panel Listrik Utama',
                'type' => ScheduleType::PERAWATAN,
                'subtype' => ScheduleSubtype::MAINTENANCE,
                'status' => ScheduleStatus::IN_PROGRESS,
                'notes' => 'Pengecekan beban listrik dan kondisi kabel panel utama.',
                'start_time' => Carbon::now()->addDays(5)->setHour(9),
                'end_time' => Carbon::now()->addDays(5)->setHour(11),
            ],
        ];

        foreach ($schedules as $data) {
            MaintenanceSchedule::create(array_merge($data, [
                'created_by' => $admin->id,
            ]));
        }
    }
}
