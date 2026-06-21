<?php

use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalDibuat;
use App\Events\Jadwal\JadwalSewaAktif;
use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserProfile;
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

test('[BERHASIL] aktifkanJadwal berhasil dari status terkonfirmasi', function () {
    Event::fake([JadwalSewaAktif::class]);

    $schedule = Schedule::create([
        'room_id'    => 1,
        'type'       => ScheduleType::SEWA->value,
        'status'     => ScheduleStatus::TERKONFIRMASI->value,
        'start_date' => now()->toDateString(),
        'end_date'   => now()->addDays(30)->toDateString(),
    ]);

    $service = app(ScheduleService::class);
    $updated = $service->aktifkanJadwal($schedule->id);

    expect($updated->status)->toBe(ScheduleStatus::ACTIVE);
    expect($updated->activated_at)->not->toBeNull();
    Event::assertDispatched(JadwalSewaAktif::class);
});

test('[BERHASIL] konfirmasiJadwal mengubah status pending ke terkonfirmasi', function () {
    Event::fake([JadwalSewaAktif::class]);

    $schedule = Schedule::create([
        'room_id'    => 2,
        'type'       => ScheduleType::SEWA->value,
        'status'     => ScheduleStatus::PENDING->value,
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date'   => now()->addDays(40)->toDateString(),
    ]);

    $service = app(ScheduleService::class);
    $updated = $service->konfirmasiJadwal($schedule->id);

    expect($updated->status)->toBe(ScheduleStatus::TERKONFIRMASI);
    Event::assertNotDispatched(JadwalSewaAktif::class);
});

test('[BERHASIL] konfirmasiJadwal mengubah status dp_terbayar ke terkonfirmasi', function () {
    $schedule = Schedule::create([
        'room_id'        => 3,
        'type'           => ScheduleType::SEWA->value,
        'status'         => ScheduleStatus::DP_TERBAYAR->value,
        'start_date'     => now()->addDays(10)->toDateString(),
        'end_date'       => now()->addDays(40)->toDateString(),
    ]);

    $service = app(ScheduleService::class);
    $updated = $service->konfirmasiJadwal($schedule->id);

    expect($updated->status)->toBe(ScheduleStatus::TERKONFIRMASI);
});

test('[GAGAL] konfirmasiJadwal gagal jika status sudah active', function () {
    $schedule = Schedule::create([
        'room_id'    => 4,
        'type'       => ScheduleType::SEWA->value,
        'status'     => ScheduleStatus::ACTIVE->value,
        'start_date' => '2026-06-01',
        'end_date'   => '2026-07-01',
    ]);

    $service = app(ScheduleService::class);

    expect(fn () => $service->konfirmasiJadwal($schedule->id))
        ->toThrow(\DomainException::class);
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

test('[GAGAL] buatJadwal ditolak jika kamar sudah memiliki jadwal pending', function () {
    Event::fake([JadwalDibuat::class]);

    Schedule::create([
        'room_id' => 10,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::PENDING->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
    ]);

    $service = app(ScheduleService::class);

    expect(fn () => $service->buatJadwal([
        'room_id' => 10,
        'type' => 'sewa',
        'start_date' => '2026-07-01',
        'end_date' => '2026-08-01',
    ]))->toThrow(\DomainException::class);

    Event::assertNotDispatched(JadwalDibuat::class);
});

test('[GAGAL] buatJadwal ditolak jika kamar sudah memiliki jadwal active', function () {
    Event::fake([JadwalDibuat::class]);

    Schedule::create([
        'room_id' => 11,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::ACTIVE->value,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
    ]);

    $service = app(ScheduleService::class);

    expect(fn () => $service->buatJadwal([
        'room_id' => 11,
        'type' => 'sewa',
        'start_date' => '2026-07-01',
        'end_date' => '2026-08-01',
    ]))->toThrow(\DomainException::class);

    Event::assertNotDispatched(JadwalDibuat::class);
});

test('[BERHASIL] buatJadwal berhasil jika kamar hanya memiliki jadwal finished', function () {
    Event::fake([JadwalDibuat::class]);

    Schedule::create([
        'room_id' => 12,
        'type' => ScheduleType::SEWA->value,
        'status' => ScheduleStatus::FINISHED->value,
        'start_date' => '2026-05-01',
        'end_date' => '2026-06-01',
    ]);

    $service = app(ScheduleService::class);

    $schedule = $service->buatJadwal([
        'room_id' => 12,
        'type' => 'sewa',
        'start_date' => '2026-07-01',
        'end_date' => '2026-08-01',
    ]);

    expect($schedule->status)->toBe(ScheduleStatus::PENDING);
    Event::assertDispatched(JadwalDibuat::class);
});

test('[GAGAL] buatJadwal ditolak jika tenant_user_id ada tapi profil belum diisi', function () {
    Event::fake([JadwalDibuat::class]);

    $user = User::factory()->create();
    $service = app(ScheduleService::class);

    expect(fn () => $service->buatJadwal([
        'room_id' => 20,
        'type' => 'sewa',
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
        'tenant_user_id' => $user->id,
    ]))->toThrow(\DomainException::class, 'Profil belum lengkap');

    Event::assertNotDispatched(JadwalDibuat::class);
});

test('[GAGAL] buatJadwal ditolak jika profil ada tapi field wajib tidak lengkap', function () {
    Event::fake([JadwalDibuat::class]);

    $user = User::factory()->create();
    UserProfile::create([
        'user_id'        => $user->id,
        'id_card_number' => '',
        'phone_number'   => '08123456789',
        'gender'         => 'male',
        'address_ktp'    => 'Jl. Contoh',
    ]);
    $service = app(ScheduleService::class);

    expect(fn () => $service->buatJadwal([
        'room_id' => 20,
        'type' => 'sewa',
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
        'tenant_user_id' => $user->id,
    ]))->toThrow(\DomainException::class, 'Profil belum lengkap');

    Event::assertNotDispatched(JadwalDibuat::class);
});

test('[BERHASIL] buatJadwal berhasil jika tenant_user_id ada dan profil lengkap', function () {
    Event::fake([JadwalDibuat::class]);

    $user = User::factory()->create();
    UserProfile::create([
        'user_id'        => $user->id,
        'id_card_number' => '1234567890123456',
        'phone_number'   => '08123456789',
        'gender'         => 'male',
        'address_ktp'    => 'Jl. Contoh No. 1, Gorontalo',
    ]);
    $service = app(ScheduleService::class);

    $schedule = $service->buatJadwal([
        'room_id' => 21,
        'type' => 'sewa',
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
        'tenant_user_id' => $user->id,
        'tenant_name' => $user->name,
        'agreed_price' => 500000,
    ]);

    expect($schedule)->toBeInstanceOf(Schedule::class);
    expect($schedule->status)->toBe(ScheduleStatus::PENDING);
    Event::assertDispatched(JadwalDibuat::class);
});

test('[BERHASIL] buatJadwal mengisi tenant_phone dari profil jika tidak dikirim', function () {
    Event::fake([JadwalDibuat::class]);

    $user = User::factory()->create();
    UserProfile::create([
        'user_id'        => $user->id,
        'id_card_number' => '1234567890123456',
        'phone_number'   => '08199999999',
        'gender'         => 'male',
        'address_ktp'    => 'Jl. Contoh No. 1, Gorontalo',
    ]);
    $service = app(ScheduleService::class);

    $schedule = $service->buatJadwal([
        'room_id' => 22,
        'type' => 'sewa',
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-01',
        'tenant_user_id' => $user->id,
        'tenant_name' => $user->name,
        'agreed_price' => 500000,
        // tenant_phone sengaja tidak dikirim
    ]);

    expect($schedule->tenant_phone)->toBe('08199999999');

    Event::assertDispatched(JadwalDibuat::class, fn ($e) => $e->tenantPhone === '08199999999');
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
