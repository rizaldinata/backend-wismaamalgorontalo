<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Auth\Models\User;
use Modules\Maintenance\Enums\MaintenanceStatus;
use Modules\Maintenance\Models\MaintenanceRequest;
use Modules\Room\Models\Room;
use Modules\Setting\Models\FeatureToggle;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    Permission::firstOrCreate(['name' => 'create-damage-report', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'view-damage-report', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'view-my-damage-report', 'guard_name' => 'api']);

    $facilityParent = FeatureToggle::firstOrCreate(
        ['key' => 'facility_management'],
        ['name' => 'Fasilitas & Pemeliharaan', 'is_active' => true, 'is_locked' => false],
    );
    FeatureToggle::firstOrCreate(
        ['key' => 'damage_report'],
        ['name' => 'Laporan Kerusakan', 'is_active' => true, 'is_locked' => false, 'parent_id' => $facilityParent->id],
    );
});

test('[BERHASIL] resident dapat mengirim laporan kerusakan baru', function () {
    $resident = User::factory()->create();
    $resident->givePermissionTo('create-damage-report');

    $room = Room::factory()->create();

    $response = $this->actingAs($resident)->postJson('/api/v1/damage-reports', [
        'title' => 'Lampu Mati',
        'room_id' => $room->id,
        'description' => 'Lampu kamar mandi mati',
        'location' => 'Kamar Mandi',
        'reporter_phone' => '08123456789',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.description', 'Lampu kamar mandi mati')
        ->assertJsonPath('data.location', 'Kamar Mandi');

    $this->assertDatabaseHas('maintenance_requests', [
        'room_id' => $room->id,
        'description' => 'Lampu kamar mandi mati',
        'location' => 'Kamar Mandi',
        'reporter_user_id' => $resident->id,
    ]);
});

test('[BERHASIL] resident dapat melihat daftar laporan miliknya', function () {
    $resident = User::factory()->create();
    $resident->givePermissionTo('view-my-damage-report');

    $room = Room::factory()->create();

    MaintenanceRequest::create([
        'title' => 'Lampu Mati',
        'room_id' => $room->id,
        'reporter_user_id' => $resident->id,
        'reporter_name' => 'Test Resident',
        'description' => 'Lampu rusak',
        'location' => 'Kamar Mandi',
        'status' => MaintenanceStatus::PENDING->value,
        'reported_at' => now(),
    ]);

    $response = $this->actingAs($resident)->getJson('/api/v1/damage-reports/my-reports');

    $response->assertStatus(200)
        ->assertJsonFragment(['title' => 'Lampu Mati'])
        ->assertJsonFragment(['location' => 'Kamar Mandi']);
});

test('[BERHASIL] admin dapat mengupdate status laporan', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('view-damage-report');

    $room = Room::factory()->create();

    $report = MaintenanceRequest::create([
        'title' => 'Lampu Mati',
        'room_id' => $room->id,
        'reporter_user_id' => User::factory()->create()->id,
        'reporter_name' => 'Test User',
        'description' => 'Lampu rusak',
        'status' => MaintenanceStatus::PENDING->value,
        'reported_at' => now(),
    ]);

    $response = $this->actingAs($admin)->postJson("/api/v1/damage-reports/admin/{$report->id}/updates", [
        'description' => 'Lampu sudah diganti',
        'status' => MaintenanceStatus::COMPLETED->value,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $report->id,
        'status' => MaintenanceStatus::COMPLETED->value,
    ]);

    $this->assertDatabaseHas('maintenance_request_updates', [
        'maintenance_request_id' => $report->id,
        'description' => 'Lampu sudah diganti',
    ]);
});

test('[GAGAL] request ditolak jika resident tidak memiliki permission', function () {
    $resident = User::factory()->create();
    // No permission given

    $room = Room::factory()->create();

    $response = $this->actingAs($resident)->postJson('/api/v1/damage-reports', [
        'title' => 'Lampu Mati',
        'room_id' => $room->id,
        'description' => 'Lampu kamar mandi mati',
    ]);

    $response->assertStatus(403);
});
