<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Maintenance\Enums\ScheduleStatus;
use Modules\Maintenance\Enums\ScheduleType;
use Modules\Maintenance\Services\ScheduleService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('[BERHASIL] create membuat jadwal perawatan dan menyisipkan update awal', function () {
    $user = User::factory()->create();

    $service = app(ScheduleService::class);

    $schedule = $service->create($user->id, [
        'type' => ScheduleType::PEMBERSIHAN->value,
        'subtype' => 'rutin',
        'technician_name' => 'John Doe',
        'location' => 'Kamar 101',
        'start_time' => now()->format('Y-m-d H:i:s'),
        'status' => ScheduleStatus::IN_PROGRESS->value,
        'notes' => 'Pembersihan rutin',
    ]);

    expect($schedule->location)->toBe('Kamar 101');
    expect($schedule->type)->toBe(ScheduleType::PEMBERSIHAN);

    // Pastikan ada update awal yang dibuat oleh service
    expect($schedule->updates()->count())->toBe(1);
    expect($schedule->updates()->first()->notes)->toBe('Jadwal pemeliharaan telah dibuat.');
});

test('[BERHASIL] addUpdate menambahkan progres dan memperbarui status jadwal', function () {
    $user = User::factory()->create();

    $service = app(ScheduleService::class);

    $schedule = $service->create($user->id, [
        'type' => ScheduleType::PEMBERSIHAN->value,
        'subtype' => 'rutin',
        'technician_name' => 'John Doe',
        'location' => 'Kamar 101',
        'start_time' => now()->format('Y-m-d H:i:s'),
        'status' => ScheduleStatus::IN_PROGRESS->value,
        'notes' => 'Pembersihan rutin',
    ]);

    $update = $service->addUpdate($user->id, $schedule->id, [
        'notes' => 'Pembersihan selesai',
        'status' => ScheduleStatus::DONE->value,
    ]);

    expect($update->notes)->toBe('Pembersihan selesai');
    expect($schedule->fresh()->status)->toBe(ScheduleStatus::DONE);

    // Total updates: 1 initial + 1 new
    expect($schedule->updates()->count())->toBe(2);
});
