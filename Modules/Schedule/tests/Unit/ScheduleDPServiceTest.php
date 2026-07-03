<?php

use App\Events\Jadwal\JadwalDibuat;
use App\Events\Jadwal\JadwalSewaAktif;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Schedule\Enums\SchedulePaymentScheme;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Enums\ScheduleType;
use Modules\Schedule\Models\Schedule;
use Modules\Schedule\Services\ScheduleService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('[BERHASIL] buatJadwal dengan payment_scheme=dp menyimpan dp_amount sebesar 50% dari agreed_price', function () {
    Event::fake([JadwalDibuat::class]);

    $service = app(ScheduleService::class);
    $startDate = now()->addDays(10)->toDateString();
    $endDate = now()->addDays(40)->toDateString();

    $schedule = $service->buatJadwal([
        'room_id' => 1,
        'type' => 'sewa',
        'payment_scheme' => 'dp',
        'start_date' => $startDate,
        'end_date' => $endDate,
        'tenant_name' => 'Budi Santoso',
        'tenant_phone' => '08123456789',
        'agreed_price' => 1000000,
    ]);

    expect($schedule->payment_scheme)->toBe(SchedulePaymentScheme::DP);
    expect((float) $schedule->dp_amount)->toBe(500000.0);
});

test('[BERHASIL] buatJadwal dengan payment_scheme=dp memicu JadwalDibuat dengan dpAmount', function () {
    Event::fake([JadwalDibuat::class]);

    $service = app(ScheduleService::class);
    $startDate = now()->addDays(10)->toDateString();

    $schedule = $service->buatJadwal([
        'room_id' => 1,
        'type' => 'sewa',
        'payment_scheme' => 'dp',
        'start_date' => $startDate,
        'end_date' => now()->addDays(40)->toDateString(),
        'agreed_price' => 800000,
    ]);

    Event::assertDispatched(JadwalDibuat::class, function ($e) use ($schedule) {
        return $e->scheduleId === $schedule->id
            && $e->paymentScheme === 'dp'
            && $e->dpAmount === 400000.0;
    });
});

test('[GAGAL] buatJadwal dengan payment_scheme=dp ditolak jika start_date <= 7 hari ke depan', function () {
    Event::fake([JadwalDibuat::class]);

    $service = app(ScheduleService::class);

    expect(fn () => $service->buatJadwal([
        'room_id' => 1,
        'type' => 'sewa',
        'payment_scheme' => 'dp',
        'start_date' => now()->addDays(5)->toDateString(),
        'end_date' => now()->addDays(35)->toDateString(),
        'agreed_price' => 500000,
    ]))->toThrow(\DomainException::class, 'lebih dari 7 hari');

    Event::assertNotDispatched(JadwalDibuat::class);
});

test('[BERHASIL] buatJadwal dengan payment_scheme=full tidak menyimpan dp_amount', function () {
    Event::fake([JadwalDibuat::class]);

    $service = app(ScheduleService::class);

    $schedule = $service->buatJadwal([
        'room_id' => 1,
        'type' => 'sewa',
        'payment_scheme' => 'full',
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(33)->toDateString(),
        'agreed_price' => 500000,
    ]);

    expect($schedule->payment_scheme)->toBe(SchedulePaymentScheme::FULL);
    expect($schedule->dp_amount)->toBeNull();
});

test('[BERHASIL] aktifkanJadwalDariDP mengubah status dp_terbayar ke active', function () {
    Event::fake([JadwalSewaAktif::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::DP_TERBAYAR->value,
        'payment_scheme' => SchedulePaymentScheme::DP->value,
        'dp_amount' => 500000,
        'dp_paid_at' => now(),
        'start_date' => now()->addDays(5)->toDateString(),
        'end_date' => now()->addDays(35)->toDateString(),
        'agreed_price' => 1000000,
    ]);

    $service = app(ScheduleService::class);
    $updated = $service->aktifkanJadwalDariDP($schedule->id);

    expect($updated->status)->toBe(ScheduleStatus::ACTIVE);
    expect($updated->activated_at)->not->toBeNull();
    Event::assertDispatched(JadwalSewaAktif::class, fn ($e) => $e->scheduleId === $schedule->id);
});

test('[GAGAL] aktifkanJadwalDariDP gagal jika status bukan dp_terbayar', function () {
    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::PENDING->value,
        'start_date' => now()->addDays(5)->toDateString(),
        'end_date' => now()->addDays(35)->toDateString(),
    ]);

    $service = app(ScheduleService::class);

    expect(fn () => $service->aktifkanJadwalDariDP($schedule->id))
        ->toThrow(\DomainException::class);
});

test('[BERHASIL] batalkanJadwal bisa dilakukan dari status dp_terbayar', function () {
    Event::fake();

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::DP_TERBAYAR->value,
        'payment_scheme' => SchedulePaymentScheme::DP->value,
        'dp_amount' => 500000,
        'dp_paid_at' => now(),
        'start_date' => now()->addDays(5)->toDateString(),
        'end_date' => now()->addDays(35)->toDateString(),
        'agreed_price' => 1000000,
    ]);

    $service = app(ScheduleService::class);
    $updated = $service->batalkanJadwal($schedule->id);

    expect($updated->status)->toBe(ScheduleStatus::CANCELLED);
});

test('[BERHASIL] hasPendingOrActiveByRoomId = true jika ada jadwal dp_terbayar di kamar yang sama', function () {
    Schedule::create([
        'room_id' => 99,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::DP_TERBAYAR->value,
        'payment_scheme' => SchedulePaymentScheme::DP->value,
        'start_date' => now()->addDays(5)->toDateString(),
        'end_date' => now()->addDays(35)->toDateString(),
    ]);

    $service = app(ScheduleService::class);

    expect(fn () => $service->buatJadwal([
        'room_id' => 99,
        'type' => 'sewa',
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addDays(40)->toDateString(),
        'agreed_price' => 500000,
    ]))->toThrow(\DomainException::class);
});

test('[BERHASIL] hasPendingOrActiveByRoomId = true jika ada jadwal terkonfirmasi di kamar yang sama', function () {
    Schedule::create([
        'room_id' => 98,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::TERKONFIRMASI->value,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(33)->toDateString(),
    ]);

    $service = app(ScheduleService::class);

    expect(fn () => $service->buatJadwal([
        'room_id' => 98,
        'type' => 'sewa',
        'start_date' => now()->addDays(5)->toDateString(),
        'end_date' => now()->addDays(35)->toDateString(),
        'agreed_price' => 500000,
    ]))->toThrow(\DomainException::class);
});
