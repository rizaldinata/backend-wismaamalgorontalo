<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Auth\Models\User;
use Modules\Maintenance\Enums\ScheduleStatus;
use Modules\Maintenance\Enums\ScheduleType;
use Modules\Maintenance\Models\MaintenanceSchedule;
use Modules\Setting\Models\FeatureToggle;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    Permission::firstOrCreate(['name' => 'schedule-maintenance', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'view-maintenance', 'guard_name' => 'api']);

    $facilityParent = FeatureToggle::firstOrCreate(
        ['key' => 'facility_management'],
        ['name' => 'Fasilitas & Pemeliharaan', 'is_active' => true, 'is_locked' => false],
    );
    FeatureToggle::firstOrCreate(
        ['key' => 'maintenance_schedule'],
        ['name' => 'Jadwal Maintenance', 'is_active' => true, 'is_locked' => false, 'parent_id' => $facilityParent->id],
    );
});

test('[BERHASIL] admin dapat membuat jadwal pemeliharaan', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('schedule-maintenance');
    
    $response = $this->actingAs($admin)->postJson('/api/v1/schedules', [
        'type' => ScheduleType::PERAWATAN->value,
        'subtype' => 'perbaikan',
        'technician_name' => 'John Doe',
        'location' => 'Kamar 101',
        'start_time' => now()->addDays(2)->format('Y-m-d H:i:s'),
        'status' => ScheduleStatus::IN_PROGRESS->value,
        'notes' => 'Pembersihan AC bulanan'
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('data.type', ScheduleType::PERAWATAN->value);

    $this->assertDatabaseHas('maintenance_schedules', [
        'location' => 'Kamar 101',
        'type' => ScheduleType::PERAWATAN->value,
        'subtype' => 'perbaikan'
    ]);
});

test('[BERHASIL] admin dapat menambahkan update pada jadwal', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('schedule-maintenance');
    
    $schedule = MaintenanceSchedule::create([
        'type' => ScheduleType::PERAWATAN->value,
        'subtype' => 'perbaikan',
        'technician_name' => 'John Doe',
        'location' => 'Kamar 101',
        'start_time' => now()->format('Y-m-d H:i:s'),
        'status' => ScheduleStatus::IN_PROGRESS->value,
        'created_by' => $admin->id
    ]);

    $response = $this->actingAs($admin)->postJson("/api/v1/schedules/{$schedule->id}/updates", [
        'notes' => 'Teknisi sudah datang',
        'status' => ScheduleStatus::DONE->value
    ]);

    $response->assertStatus(201);
    
    $this->assertDatabaseHas('maintenance_schedules', [
        'id' => $schedule->id,
        'status' => ScheduleStatus::DONE->value
    ]);
    
    $this->assertDatabaseHas('maintenance_schedule_updates', [
        'maintenance_schedule_id' => $schedule->id,
        'notes' => 'Teknisi sudah datang'
    ]);
});

test('[GAGAL] request membuat jadwal ditolak jika tidak memiliki permission', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->postJson('/api/v1/schedules', [
        'type' => ScheduleType::PEMBERSIHAN->value,
        'technician_name' => 'John Doe',
        'location' => 'Kamar 101',
        'start_time' => now()->format('Y-m-d H:i:s'),
    ]);

    $response->assertStatus(403);
});
