<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Enums\ScheduleStatus;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Seed minimal: user, resident, room, lease
    $userId = DB::table('users')->insertGetId([
        'name'              => 'Budi Test',
        'email'             => 'budi@test.com',
        'password'          => bcrypt('password'),
        'email_verified_at' => now(),
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    $residentId = DB::table('residents')->insertGetId([
        'user_id'       => $userId,
        'id_card_number'=> '1234567890',
        'phone_number'  => '08123456789',
        'gender'        => 'male',
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);

    $roomId = DB::table('rooms')->insertGetId([
        'number'     => '101',
        'price'      => '750000',
        'status'     => 'available',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->leaseId = DB::table('leases')->insertGetId([
        'resident_id' => $residentId,
        'room_id'     => $roomId,
        'start_date'  => '2026-01-01',
        'end_date'    => '2026-02-01',
        'status'      => 'active',
        'rental_type' => 'monthly',
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $this->invoiceId = DB::table('invoices')->insertGetId([
        'lease_id'       => $this->leaseId,
        'invoice_number' => 'INV-TEST-001',
        'amount'         => 750000,
        'status'         => 'unpaid',
        'due_date'       => '2026-01-01',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);
});

test('command migrasi menyalin lease ke room_schedules', function () {
    $this->artisan('schedule:migrasi-dari-rental')
        ->assertExitCode(0);

    $schedule = DB::table('room_schedules')
        ->where('legacy_lease_id', $this->leaseId)
        ->first();

    expect($schedule)->not->toBeNull();
    expect($schedule->type)->toBe('sewa');
    expect($schedule->status)->toBe(ScheduleStatus::ACTIVE->value);
    expect($schedule->tenant_name)->toBe('Budi Test');
    expect($schedule->tenant_phone)->toBe('08123456789');
    expect($schedule->tenant_id_number)->toBe('1234567890');
    expect($schedule->agreed_price)->toBe('750000.00');
});

test('command migrasi mengisi schedule_id pada invoice terkait', function () {
    $this->artisan('schedule:migrasi-dari-rental')
        ->assertExitCode(0);

    $schedule = DB::table('room_schedules')
        ->where('legacy_lease_id', $this->leaseId)
        ->first();

    $invoice = DB::table('invoices')->where('id', $this->invoiceId)->first();

    expect($invoice->schedule_id)->toBe($schedule->id);
});

test('command migrasi tidak duplikasi jika dijalankan ulang dengan --skip-existing', function () {
    $this->artisan('schedule:migrasi-dari-rental')->assertExitCode(0);
    $this->artisan('schedule:migrasi-dari-rental --skip-existing')->assertExitCode(0);

    $count = DB::table('room_schedules')
        ->where('legacy_lease_id', $this->leaseId)
        ->count();

    expect($count)->toBe(1);
});

test('dry-run tidak menyimpan data ke database', function () {
    $this->artisan('schedule:migrasi-dari-rental --dry-run')->assertExitCode(0);

    $count = DB::table('room_schedules')->count();
    expect($count)->toBe(0);
});

test('jumlah room_schedules sama dengan jumlah leases setelah migrasi', function () {
    $leaseCount = DB::table('leases')->whereNull('deleted_at')->count();

    $this->artisan('schedule:migrasi-dari-rental')->assertExitCode(0);

    $scheduleCount = DB::table('room_schedules')
        ->whereNotNull('legacy_lease_id')
        ->count();

    expect($scheduleCount)->toBe($leaseCount);
});
