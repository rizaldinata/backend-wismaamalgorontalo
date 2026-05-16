<?php

use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalDibuat;
use App\Events\Jadwal\JadwalSewaAktif;
use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Enums\ScheduleType;
use Modules\Schedule\Models\Schedule;
use Modules\Schedule\Services\ScheduleService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('buatJadwal membuat record dan mengirim event JadwalDibuat', function () {
    Event::fake([JadwalDibuat::class]);

    $service = app(ScheduleService::class);

    $schedule = $service->buatJadwal([
        'room_id' => 1,
        'type' => 'sewa',
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
        'tenant_name' => 'Budi Santoso',
        'tenant_phone' => '08123456789',
        'agreed_price' => 750000,
    ]);

    expect($schedule)->toBeInstanceOf(Schedule::class);
    expect($schedule->status)->toBe(ScheduleStatus::PENDING);
    expect($schedule->type)->toBe(ScheduleType::SEWA);

    Event::assertDispatched(JadwalDibuat::class, function ($event) use ($schedule) {
        return $event->scheduleId === $schedule->id
            && $event->tipeJadwal === 'sewa'
            && $event->agreedPrice === 750000.0;
    });
});

test('aktifkanJadwal mengubah status ke active dan mengirim event JadwalSewaAktif', function () {
    Event::fake([JadwalSewaAktif::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::PENDING->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
    ]);

    $service = app(ScheduleService::class);
    $updated = $service->aktifkanJadwal($schedule->id);

    expect($updated->status)->toBe(ScheduleStatus::ACTIVE);
    expect($updated->activated_at)->not->toBeNull();

    Event::assertDispatched(JadwalSewaAktif::class, fn ($e) => $e->scheduleId === $schedule->id);
});

test('aktifkanJadwal hanya bisa dari status pending', function () {
    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::ACTIVE->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
    ]);

    $service = app(ScheduleService::class);

    expect(fn () => $service->aktifkanJadwal($schedule->id))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('selesaikanJadwal mengubah status ke finished dan mengirim event JadwalSewaSelesai', function () {
    Event::fake([JadwalSewaSelesai::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::ACTIVE->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
    ]);

    $service = app(ScheduleService::class);
    $updated = $service->selesaikanJadwal($schedule->id);

    expect($updated->status)->toBe(ScheduleStatus::FINISHED);
    expect($updated->finished_at)->not->toBeNull();

    Event::assertDispatched(JadwalSewaSelesai::class, fn ($e) => $e->scheduleId === $schedule->id);
});

test('batalkanJadwal mengirim event JadwalBatal', function () {
    Event::fake([JadwalBatal::class]);

    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::PENDING->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
    ]);

    $service = app(ScheduleService::class);
    $updated = $service->batalkanJadwal($schedule->id);

    expect($updated->status)->toBe(ScheduleStatus::CANCELLED);

    Event::assertDispatched(JadwalBatal::class, fn ($e) => $e->scheduleId === $schedule->id);
});

test('batalkanJadwal tidak bisa dilakukan pada jadwal yang sudah selesai', function () {
    $schedule = Schedule::create([
        'room_id' => 1,
        'type' => ScheduleType::KEBERSIHAN->value,
        'status' => ScheduleStatus::FINISHED->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-02',
    ]);

    $service = app(ScheduleService::class);

    expect(fn () => $service->batalkanJadwal($schedule->id))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('ambilJadwalAktifKamar mengembalikan jadwal active atau null', function () {
    Schedule::create([
        'room_id' => 5,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::ACTIVE->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
    ]);

    $service = app(ScheduleService::class);

    expect($service->ambilJadwalAktifKamar(5))->toBeInstanceOf(Schedule::class);
    expect($service->ambilJadwalAktifKamar(99))->toBeNull();
});
