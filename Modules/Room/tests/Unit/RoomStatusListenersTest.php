<?php

use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalDibuat;
use App\Events\Jadwal\JadwalSewaAktif;
use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Room\Enums\RoomStatus;
use Modules\Room\Listeners\TandaiKamarDipesanSetelahJadwalDibuat;
use Modules\Room\Listeners\TandaiKamarTerisiSetelahJadwalAktif;
use Modules\Room\Listeners\TandaiKamarTersediaSetelahJadwalSelesai;
use Modules\Room\Models\Room;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function buatKamar(RoomStatus $status = RoomStatus::AVAILABLE): Room
{
    return Room::create([
        'number' => '405',
        'title' => 'Kamar Standar',
        'status' => $status->value,
        'price' => 750000,
        'facilities' => [],
    ]);
}

// --- TandaiKamarDipesanSetelahJadwalDibuat ---

test('[BERHASIL] kamar menjadi reserved setelah jadwal sewa dibuat', function () {
    $room = buatKamar(RoomStatus::AVAILABLE);

    $event = new JadwalDibuat(
        scheduleId: 1, roomId: $room->id, roomNumber: '405',
        tipeJadwal: 'sewa', startDate: '2026-06-01', endDate: '2026-07-01',
    );

    app(TandaiKamarDipesanSetelahJadwalDibuat::class)->handle($event);

    expect($room->fresh()->status)->toBe(RoomStatus::RESERVED);
});

test('[BERHASIL] kamar tidak berubah jika tipe jadwal bukan sewa', function () {
    $room = buatKamar(RoomStatus::AVAILABLE);

    $event = new JadwalDibuat(
        scheduleId: 1, roomId: $room->id, roomNumber: '405',
        tipeJadwal: 'maintenance', startDate: '2026-06-01', endDate: '2026-07-01',
    );

    app(TandaiKamarDipesanSetelahJadwalDibuat::class)->handle($event);

    expect($room->fresh()->status)->toBe(RoomStatus::AVAILABLE);
});

// --- TandaiKamarTerisiSetelahJadwalAktif ---

test('[BERHASIL] kamar menjadi occupied setelah jadwal sewa aktif', function () {
    $room = buatKamar(RoomStatus::RESERVED);

    $event = new JadwalSewaAktif(
        scheduleId: 1, roomId: $room->id, roomNumber: '405',
        tenantName: 'Budi', tenantPhone: '0812', startDate: '2026-06-01',
    );

    app(TandaiKamarTerisiSetelahJadwalAktif::class)->handle($event);

    expect($room->fresh()->status)->toBe(RoomStatus::OCCUPIED);
});

// --- TandaiKamarTersediaSetelahJadwalSelesai ---

test('[BERHASIL] kamar menjadi available setelah jadwal sewa selesai', function () {
    $room = buatKamar(RoomStatus::OCCUPIED);

    $event = new JadwalSewaSelesai(
        scheduleId: 1, roomId: $room->id, roomNumber: '405',
        tenantName: 'Budi', tenantPhone: '0812', endDate: '2026-07-01',
    );

    app(TandaiKamarTersediaSetelahJadwalSelesai::class)->handle($event);

    expect($room->fresh()->status)->toBe(RoomStatus::AVAILABLE);
});

test('[BERHASIL] kamar menjadi available setelah jadwal dibatalkan', function () {
    $room = buatKamar(RoomStatus::RESERVED);

    $event = new JadwalBatal(
        scheduleId: 1, roomId: $room->id, roomNumber: '405',
        tipeJadwal: 'sewa',
    );

    app(TandaiKamarTersediaSetelahJadwalSelesai::class)->handle($event);

    expect($room->fresh()->status)->toBe(RoomStatus::AVAILABLE);
});
