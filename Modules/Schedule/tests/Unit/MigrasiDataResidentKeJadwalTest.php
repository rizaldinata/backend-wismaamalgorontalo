<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $userId = DB::table('users')->insertGetId([
        'name'              => 'Citra Test',
        'email'             => 'citra@test.com',
        'password'          => bcrypt('password'),
        'email_verified_at' => now(),
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $residentId = DB::table('residents')->insertGetId([
        'user_id'        => $userId,
        'id_card_number' => '9876543210',
        'phone_number'   => '08199999999',
        'gender'         => 'female',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    $roomId = DB::table('rooms')->insertGetId([
        'number'     => '202',
        'price'      => '800000',
        'status'     => 'available',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $leaseId = DB::table('leases')->insertGetId([
        'resident_id' => $residentId,
        'room_id'     => $roomId,
        'start_date'  => '2026-01-01',
        'end_date'    => '2026-02-01',
        'status'      => 'active',
        'rental_type' => 'monthly',
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    // room_schedule tanpa data penghuni (tenant_name NULL)
    $this->scheduleId = DB::table('room_schedules')->insertGetId([
        'legacy_lease_id' => $leaseId,
        'room_id'         => $roomId,
        'type'            => 'sewa',
        'status'          => 'active',
        'start_date'      => '2026-01-01',
        'end_date'        => '2026-02-01',
        'tenant_user_id'  => null,
        'tenant_name'     => null,
        'tenant_phone'    => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $this->userId     = $userId;
    $this->residentId = $residentId;
    $this->leaseId    = $leaseId;
});

test('dry-run menampilkan statistik tanpa mengubah data', function () {
    $this->artisan('schedule:verifikasi-data-penghuni --dry-run')
        ->assertExitCode(0);

    $schedule = DB::table('room_schedules')->where('id', $this->scheduleId)->first();
    expect($schedule->tenant_name)->toBeNull();
});

test('tanpa opsi --fix hanya menampilkan peringatan', function () {
    $this->artisan('schedule:verifikasi-data-penghuni')
        ->assertExitCode(0);

    $schedule = DB::table('room_schedules')->where('id', $this->scheduleId)->first();
    expect($schedule->tenant_name)->toBeNull();
});

test('command --fix mengisi data penghuni yang kosong dari residents', function () {
    $this->artisan('schedule:verifikasi-data-penghuni --fix')
        ->assertExitCode(0);

    $schedule = DB::table('room_schedules')->where('id', $this->scheduleId)->first();

    expect($schedule->tenant_name)->toBe('Citra Test');
    expect($schedule->tenant_phone)->toBe('08199999999');
    expect($schedule->tenant_id_number)->toBe('9876543210');
    expect($schedule->tenant_user_id)->toBe($this->userId);
});

test('command tidak mengubah schedule yang sudah memiliki data penghuni', function () {
    DB::table('room_schedules')->where('id', $this->scheduleId)->update([
        'tenant_name'  => 'Nama Sudah Ada',
        'tenant_phone' => '08100000000',
    ]);

    $this->artisan('schedule:verifikasi-data-penghuni --fix')
        ->assertExitCode(0);

    $schedule = DB::table('room_schedules')->where('id', $this->scheduleId)->first();
    expect($schedule->tenant_name)->toBe('Nama Sudah Ada');
});

test('schedule tanpa legacy_lease_id dilewati oleh --fix', function () {
    // Tambah schedule tanpa legacy_lease_id
    $noLegacyId = DB::table('room_schedules')->insertGetId([
        'legacy_lease_id' => null,
        'room_id'         => 1,
        'type'            => 'sewa',
        'status'          => 'pending',
        'start_date'      => '2026-03-01',
        'end_date'        => '2026-04-01',
        'tenant_name'     => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $this->artisan('schedule:verifikasi-data-penghuni --fix')
        ->assertExitCode(0);

    // Hanya schedule dengan legacy_lease_id yang diperbaiki
    $schedule = DB::table('room_schedules')->where('id', $noLegacyId)->first();
    expect($schedule->tenant_name)->toBeNull();
});
