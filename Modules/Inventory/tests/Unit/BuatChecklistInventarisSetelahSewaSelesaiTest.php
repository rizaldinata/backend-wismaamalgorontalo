<?php

use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Listeners\BuatChecklistInventarisSetelahSewaSelesai;
use Tests\TestCase;

uses(TestCase::class);

test('handle tidak melempar exception saat menerima event jadwal sewa selesai', function () {
    Log::shouldReceive('info')->once()->with('Inventory checklist diperlukan', [
        'schedule_id' => 1,
        'room_id' => 10,
        'room_number' => '101',
    ]);

    $event = new JadwalSewaSelesai(
        scheduleId: 1,
        roomId: 10,
        roomNumber: '101',
        tenantName: 'Budi Santoso',
        tenantPhone: '08123456789',
        endDate: '2025-06-01',
    );

    $listener = new BuatChecklistInventarisSetelahSewaSelesai;
    $listener->handle($event);
});

test('listener memiliki method handle yang menerima JadwalSewaSelesai', function () {
    $reflection = new ReflectionClass(BuatChecklistInventarisSetelahSewaSelesai::class);
    $method = $reflection->getMethod('handle');
    $params = $method->getParameters();

    expect($params)->toHaveCount(1);
    expect($params[0]->getType()->getName())->toBe(JadwalSewaSelesai::class);
});
