<?php

use App\Events\Finance\PembayaranDibatalkan;
use App\Events\Jadwal\JadwalBatal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Enums\ScheduleType;
use Modules\Schedule\Listeners\BatalkanJadwalSetelahPembayaranGagal;
use Modules\Schedule\Models\Schedule;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function buatEventDibatalkan(int $scheduleId, ?string $paymentStatus, int $invoiceId = 1): PembayaranDibatalkan
{
    return new PembayaranDibatalkan(
        paymentId: 1,
        invoiceId: $invoiceId,
        scheduleId: $scheduleId,
        tenantName: 'Budi',
        tenantPhone: '08123',
        amount: 750000.0,
        paymentStatus: $paymentStatus,
    );
}

test('[BERHASIL] jadwal pending dibatalkan ketika Midtrans expire (status failed)', function () {
    Event::fake([JadwalBatal::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::PENDING->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
    ]);

    $listener = app(BatalkanJadwalSetelahPembayaranGagal::class);
    $listener->handle(buatEventDibatalkan($schedule->id, 'failed'));

    expect($schedule->fresh()->status)->toBe(ScheduleStatus::CANCELLED);
    Event::assertDispatched(JadwalBatal::class);
});

test('[BERHASIL] jadwal aktif dibatalkan ketika refund Midtrans (status refunded)', function () {
    Event::fake([JadwalBatal::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::ACTIVE->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
        'activated_at' => now(),
    ]);

    $listener = app(BatalkanJadwalSetelahPembayaranGagal::class);
    $listener->handle(buatEventDibatalkan($schedule->id, 'refunded'));

    expect($schedule->fresh()->status)->toBe(ScheduleStatus::CANCELLED);
    Event::assertDispatched(JadwalBatal::class);
});

test('[BERHASIL] jadwal aktif TIDAK dibatalkan ketika pembayaran perpanjang gagal (status failed)', function () {
    Event::fake([JadwalBatal::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::ACTIVE->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-08-01', // end_date sudah di-update oleh perpanjangSewa
        'activated_at' => now(),
    ]);

    // Buat finance_active_tenants untuk schedule ini
    DB::table('finance_active_tenants')->insert([
        'schedule_id' => $schedule->id,
        'user_id'     => 1,
        'room_number' => '101',
        'tenant_name' => 'Budi',
        'end_date'    => '2026-08-01',
        'start_date'  => '2026-06-01',
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    // Buat invoice perpanjangan (period_start = original_end_date + 1 hari = 2026-07-02)
    $invoiceId = DB::table('invoices')->insertGetId([
        'schedule_id'    => $schedule->id,
        'invoice_number' => 'EXT-20260601-0001-XXXX',
        'amount'         => 1200000,
        'status'         => 'unpaid',
        'period_start'   => '2026-07-02',
        'period_end'     => '2026-08-01',
        'due_date'       => now()->toDateString(),
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    $listener = app(BatalkanJadwalSetelahPembayaranGagal::class);
    $listener->handle(buatEventDibatalkan($schedule->id, 'failed', $invoiceId));

    // Jadwal tetap aktif
    expect($schedule->fresh()->status)->toBe(ScheduleStatus::ACTIVE);
    Event::assertNotDispatched(JadwalBatal::class);

    // end_date di-rollback: period_start (2026-07-02) - 1 hari = 2026-07-01
    expect($schedule->fresh()->end_date->toDateString())->toBe('2026-07-01');

    // finance_active_tenants juga di-rollback
    $fat = DB::table('finance_active_tenants')->where('schedule_id', $schedule->id)->first();
    expect($fat->end_date)->toBe('2026-07-01');
});

test('[BERHASIL] jadwal tidak dibatalkan ketika admin tolak pembayaran manual (status rejected)', function () {
    Event::fake([JadwalBatal::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::PENDING->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
    ]);

    $listener = app(BatalkanJadwalSetelahPembayaranGagal::class);
    $listener->handle(buatEventDibatalkan($schedule->id, 'rejected'));

    expect($schedule->fresh()->status)->toBe(ScheduleStatus::PENDING);
    Event::assertNotDispatched(JadwalBatal::class);
});

test('[BERHASIL] listener diabaikan jika paymentStatus null', function () {
    Event::fake([JadwalBatal::class]);

    $listener = app(BatalkanJadwalSetelahPembayaranGagal::class);
    $listener->handle(buatEventDibatalkan(1, null));

    Event::assertNotDispatched(JadwalBatal::class);
});

test('[BERHASIL] listener diabaikan jika scheduleId adalah 0', function () {
    Event::fake([JadwalBatal::class]);

    $listener = app(BatalkanJadwalSetelahPembayaranGagal::class);
    $listener->handle(buatEventDibatalkan(0, 'failed'));

    Event::assertNotDispatched(JadwalBatal::class);
});

test('[BERHASIL] listener diabaikan jika jadwal sudah cancelled', function () {
    Event::fake([JadwalBatal::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::CANCELLED->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
        'finished_at' => now(),
    ]);

    $listener = app(BatalkanJadwalSetelahPembayaranGagal::class);
    $listener->handle(buatEventDibatalkan($schedule->id, 'failed'));

    Event::assertNotDispatched(JadwalBatal::class);
});
