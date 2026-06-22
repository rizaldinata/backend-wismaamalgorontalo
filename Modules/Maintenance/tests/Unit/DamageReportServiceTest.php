<?php

use App\Events\Maintenance\LaporanKerusakanMasuk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Auth\Models\User;
use Modules\Maintenance\Enums\MaintenanceStatus;
use Modules\Maintenance\Models\MaintenanceRequest;
use Modules\Maintenance\Services\DamageReportService;
use Modules\Room\Models\Room;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('[BERHASIL] createReport membuat laporan dan memicu event LaporanKerusakanMasuk', function () {
    Event::fake();

    $user = User::factory()->create();
    $room = Room::factory()->create();

    $service = app(DamageReportService::class);
    
    $report = $service->createReport($user->id, [
        'title' => 'AC Mati',
        'room_id' => $room->id,
        'description' => 'AC tidak dingin sama sekali.',
        'reporter_phone' => '081234567890'
    ]);

    expect($report->room_id)->toBe($room->id);
    expect($report->status)->toBe(MaintenanceStatus::PENDING);
    expect($report->description)->toBe('AC tidak dingin sama sekali.');
    
    Event::assertDispatched(LaporanKerusakanMasuk::class, function ($event) use ($report) {
        return $event->reportId === $report->id && 
               $event->roomId === $report->room_id &&
               $event->description === 'AC tidak dingin sama sekali.';
    });
});

test('[BERHASIL] addUpdate menambah update dan memperbarui status laporan', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create();
    $room = Room::factory()->create();

    $service = app(DamageReportService::class);
    
    $report = $service->createReport($user->id, [
        'title' => 'Keran Bocor',
        'room_id' => $room->id,
        'description' => 'Keran air bocor.',
        'reporter_phone' => '081234567890'
    ]);

    $update = $service->addUpdate($admin->id, $report->id, [
        'description' => 'Sedang diperbaiki oleh teknisi',
        'status' => MaintenanceStatus::IN_PROGRESS->value
    ]);

    expect($update->description)->toBe('Sedang diperbaiki oleh teknisi');
    expect($report->fresh()->status)->toBe(MaintenanceStatus::IN_PROGRESS);
});
