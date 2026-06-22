<?php

use App\Events\Finance\PembayaranDiterima;
use App\Events\Jadwal\JadwalSewaAktif;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Enums\ScheduleType;
use Modules\Schedule\Listeners\AktifkanJadwalSetelahPembayaranDiterima;
use Modules\Schedule\Models\Schedule;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function buatEventPembayaranDiterima(int $scheduleId): PembayaranDiterima
{
    return new PembayaranDiterima(
        paymentId: 1,
        invoiceId: 1,
        scheduleId: $scheduleId,
        amount: 750000.0,
        tenantName: 'Budi Santoso',
        tenantPhone: '08123456789',
    );
}

test('[BERHASIL] jadwal pending diaktifkan setelah pembayaran Midtrans diterima', function () {
    Event::fake([JadwalSewaAktif::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::PENDING->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
        'tenant_name' => 'Budi Santoso',
    ]);

    $listener = app(AktifkanJadwalSetelahPembayaranDiterima::class);
    $listener->handle(buatEventPembayaranDiterima($schedule->id));

    $schedule->refresh();
    expect($schedule->status)->toBe(ScheduleStatus::ACTIVE);
    Event::assertDispatched(JadwalSewaAktif::class);
});

test('[BERHASIL] listener diabaikan jika scheduleId adalah 0', function () {
    Event::fake([JadwalSewaAktif::class]);

    $listener = app(AktifkanJadwalSetelahPembayaranDiterima::class);
    $listener->handle(buatEventPembayaranDiterima(0));

    Event::assertNotDispatched(JadwalSewaAktif::class);
});

test('[BERHASIL] listener diabaikan jika jadwal sudah aktif', function () {
    Event::fake([JadwalSewaAktif::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::ACTIVE->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
        'tenant_name' => 'Budi Santoso',
        'activated_at' => now(),
    ]);

    $listener = app(AktifkanJadwalSetelahPembayaranDiterima::class);
    $listener->handle(buatEventPembayaranDiterima($schedule->id));

    $schedule->refresh();
    expect($schedule->status)->toBe(ScheduleStatus::ACTIVE);
    Event::assertNotDispatched(JadwalSewaAktif::class);
});

test('[BERHASIL] listener diabaikan jika schedule_id tidak ada di database', function () {
    $listener = app(AktifkanJadwalSetelahPembayaranDiterima::class);

    expect(fn () => $listener->handle(buatEventPembayaranDiterima(99999)))
        ->not->toThrow(\Throwable::class);
});

test('[BERHASIL] jadwal dikonfirmasi (terkonfirmasi) jika start_date belum tiba saat pembayaran Midtrans diterima', function () {
    Event::fake([JadwalSewaAktif::class]);

    $schedule = Schedule::create([
        'room_id'     => 50,
        'type'        => ScheduleType::SEWA->value,
        'status'      => ScheduleStatus::PENDING->value,
        'start_date'  => now()->addDays(5)->toDateString(), // masa depan
        'end_date'    => now()->addDays(35)->toDateString(),
        'tenant_name' => 'Budi Santoso',
    ]);

    $listener = app(AktifkanJadwalSetelahPembayaranDiterima::class);
    $listener->handle(buatEventPembayaranDiterima($schedule->id));

    $schedule->refresh();
    expect($schedule->status)->toBe(ScheduleStatus::TERKONFIRMASI);
    Event::assertNotDispatched(JadwalSewaAktif::class);
});
