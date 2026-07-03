<?php

use App\Contracts\PaymentStatusCheckerInterface;
use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Finance\Services\PaymentStatusChecker;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Enums\ScheduleType;
use Modules\Schedule\Models\Schedule;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// Bind real PaymentStatusChecker so tests that insert invoices can be properly checked
beforeEach(function () {
    $this->app->bind(PaymentStatusCheckerInterface::class, PaymentStatusChecker::class);
});

test('[BERHASIL] command menyelesaikan jadwal yang masa sewanya sudah berakhir', function () {
    Event::fake([JadwalSewaSelesai::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::ACTIVE->value,
        'start_date' => '2026-05-01',
        'end_date' => '2026-06-01',
    ]);

    $this->artisan('schedule:finish-expired')
        ->assertExitCode(0);

    $schedule->refresh();
    expect($schedule->status)->toBe(ScheduleStatus::FINISHED);
    Event::assertDispatched(JadwalSewaSelesai::class);
});

test('[BERHASIL] command melewati jadwal yang masih punya invoice perpanjangan aktif', function () {
    Event::fake([JadwalSewaSelesai::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::ACTIVE->value,
        'start_date' => '2026-05-01',
        'end_date' => '2026-06-01',
    ]);

    // Simulasi invoice perpanjangan yang masih dalam window 15 menit
    \Illuminate\Support\Facades\DB::table('invoices')->insert([
        'schedule_id' => $schedule->id,
        'invoice_number' => 'EXT-TEST-001',
        'amount' => 750000,
        'status' => 'unpaid',
        'due_date' => now()->toDateString(),
        'payment_expires_at' => now()->addMinutes(10),
        'period_start' => '2026-06-02', // > end_date (2026-06-01) → penanda extension invoice
        'period_end' => '2026-07-01',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('schedule:finish-expired')
        ->assertExitCode(0);

    $schedule->refresh();
    expect($schedule->status)->toBe(ScheduleStatus::ACTIVE); // tetap ACTIVE, tidak di-finish
    Event::assertNotDispatched(JadwalSewaSelesai::class);
});

test('[BERHASIL] command menyelesaikan jadwal ketika invoice perpanjangan sudah expired', function () {
    Event::fake([JadwalSewaSelesai::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::ACTIVE->value,
        'start_date' => '2026-05-01',
        'end_date' => '2026-06-01',
    ]);

    // Invoice perpanjangan yang sudah expired (payment_expires_at sudah lewat)
    \Illuminate\Support\Facades\DB::table('invoices')->insert([
        'schedule_id' => $schedule->id,
        'invoice_number' => 'EXT-TEST-002',
        'amount' => 750000,
        'status' => 'unpaid',
        'due_date' => now()->toDateString(),
        'payment_expires_at' => now()->subMinutes(5),
        'period_start' => '2026-06-02',
        'period_end' => '2026-07-01',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('schedule:finish-expired')
        ->assertExitCode(0);

    $schedule->refresh();
    expect($schedule->status)->toBe(ScheduleStatus::FINISHED);
    Event::assertDispatched(JadwalSewaSelesai::class);
});

test('[GAGAL] command tidak menyentuh jadwal yang statusnya bukan ACTIVE', function () {
    Event::fake([JadwalSewaSelesai::class]);

    $pending = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::PENDING->value,
        'start_date' => '2026-05-01',
        'end_date' => '2026-06-01',
    ]);

    $finished = Schedule::create([
        'room_id' => 2,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::FINISHED->value,
        'start_date' => '2026-05-01',
        'end_date' => '2026-06-01',
    ]);

    $this->artisan('schedule:finish-expired')
        ->assertExitCode(0);

    $pending->refresh();
    $finished->refresh();
    expect($pending->status)->toBe(ScheduleStatus::PENDING);
    expect($finished->status)->toBe(ScheduleStatus::FINISHED);
    Event::assertNotDispatched(JadwalSewaSelesai::class);
});

test('[GAGAL] command tidak menyentuh jadwal yang end_date belum lewat', function () {
    Event::fake([JadwalSewaSelesai::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::ACTIVE->value,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(30)->toDateString(),
    ]);

    $this->artisan('schedule:finish-expired')
        ->assertExitCode(0);

    $schedule->refresh();
    expect($schedule->status)->toBe(ScheduleStatus::ACTIVE);
    Event::assertNotDispatched(JadwalSewaSelesai::class);
});
