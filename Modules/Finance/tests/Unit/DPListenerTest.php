<?php

use App\Events\Jadwal\DPDibayar;
use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalDibuat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\InvoiceType;
use Modules\Finance\Listeners\BatalkanInvoiceSetelahJadwalBatal;
use Modules\Finance\Listeners\BuatInvoicePelunasanSetelahDPDibayar;
use Modules\Finance\Listeners\BuatInvoiceSetelahJadwalDibuat;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function buatEventDPDibayar(int $scheduleId, array $override = []): DPDibayar
{
    return new DPDibayar(
        scheduleId: $scheduleId,
        roomNumber: $override['roomNumber'] ?? 'A-01',
        tenantName: $override['tenantName'] ?? 'Budi Santoso',
        tenantPhone: $override['tenantPhone'] ?? '08123456789',
        dpAmount: $override['dpAmount'] ?? 500000.0,
        pelunasanAmount: $override['pelunasanAmount'] ?? 500000.0,
        startDate: $override['startDate'] ?? now()->addDays(10)->toDateString(),
        endDate: $override['endDate'] ?? now()->addDays(40)->toDateString(),
        roomNumberSnapshot: $override['roomNumberSnapshot'] ?? 'A-01',
        periodStart: $override['periodStart'] ?? now()->addDays(10)->toDateString(),
        periodEnd: $override['periodEnd'] ?? now()->addDays(40)->toDateString(),
        tenantUserId: $override['tenantUserId'] ?? null,
        dpInvoiceId: $override['dpInvoiceId'] ?? null,
    );
}

test('[BERHASIL] BuatInvoiceSetelahJadwalDibuat membuat invoice type=dp jika paymentScheme=dp', function () {
    $repo = $this->createMock(InvoiceRepositoryInterface::class);
    $listener = new BuatInvoiceSetelahJadwalDibuat($repo);

    $capturedData = null;
    $repo->expects($this->once())
        ->method('create')
        ->with($this->callback(function ($data) use (&$capturedData) {
            $capturedData = $data;

            return true;
        }));

    $event = new JadwalDibuat(
        scheduleId: 5,
        roomId: 10,
        roomNumber: 'A-01',
        tipeJadwal: 'sewa',
        startDate: '2026-07-01',
        endDate: '2026-08-01',
        tenantName: 'Budi Santoso',
        tenantPhone: '08123456789',
        agreedPrice: 1000000.0,
        source: 'schedule',
        tenantUserId: 1,
        paymentScheme: 'dp',
        dpAmount: 500000.0,
    );

    $listener->handle($event);

    expect($capturedData['type'])->toBe('dp');
    expect($capturedData['amount'])->toBe(500000.0);
    expect($capturedData['invoice_number'])->toContain('DP-');
});

test('[BERHASIL] BuatInvoiceSetelahJadwalDibuat membuat invoice type=sewa jika paymentScheme=full', function () {
    $repo = $this->createMock(InvoiceRepositoryInterface::class);
    $listener = new BuatInvoiceSetelahJadwalDibuat($repo);

    $capturedData = null;
    $repo->expects($this->once())
        ->method('create')
        ->with($this->callback(function ($data) use (&$capturedData) {
            $capturedData = $data;

            return true;
        }));

    $event = new JadwalDibuat(
        scheduleId: 6,
        roomId: 10,
        roomNumber: 'A-01',
        tipeJadwal: 'sewa',
        startDate: '2026-07-01',
        endDate: '2026-08-01',
        tenantName: 'Budi Santoso',
        tenantPhone: '08123456789',
        agreedPrice: 1000000.0,
        source: 'schedule',
        tenantUserId: 1,
        paymentScheme: 'full',
        dpAmount: null,
    );

    $listener->handle($event);

    expect($capturedData['type'])->toBe('sewa');
    expect($capturedData['amount'])->toBe(1000000.0);
    expect($capturedData['invoice_number'])->toContain('INV-');
});

test('[BERHASIL] BuatInvoicePelunasanSetelahDPDibayar membuat invoice type=pelunasan dengan sisa amount', function () {
    $repo = $this->createMock(InvoiceRepositoryInterface::class);
    $listener = new BuatInvoicePelunasanSetelahDPDibayar($repo);

    $capturedData = null;
    $repo->expects($this->once())
        ->method('create')
        ->with($this->callback(function ($data) use (&$capturedData) {
            $capturedData = $data;

            return true;
        }));

    $listener->handle(buatEventDPDibayar(7, ['pelunasanAmount' => 600000.0]));

    expect($capturedData['type'])->toBe('pelunasan');
    expect($capturedData['amount'])->toBe(600000.0);
    expect($capturedData['invoice_number'])->toContain('PLN-');
    expect($capturedData['schedule_id'])->toBe(7);
});

test('[BERHASIL] BatalkanInvoiceSetelahJadwalBatal membatalkan invoice unpaid, tidak sentuh yang paid', function () {
    $dpInvoice = Invoice::create([
        'schedule_id' => 10,
        'type' => InvoiceType::DP->value,
        'invoice_number' => 'DP-TEST-010',
        'amount' => 500000,
        'status' => InvoiceStatus::PAID->value,
        'due_date' => now()->toDateString(),
        'tenant_name' => 'Budi Santoso',
        'room_number' => 'A-01',
        'period_start' => now()->toDateString(),
        'period_end' => now()->addDays(30)->toDateString(),
    ]);

    $pelunasanInvoice = Invoice::create([
        'schedule_id' => 10,
        'type' => InvoiceType::PELUNASAN->value,
        'invoice_number' => 'PLN-TEST-010',
        'amount' => 500000,
        'status' => InvoiceStatus::UNPAID->value,
        'due_date' => now()->addDays(5)->toDateString(),
        'tenant_name' => 'Budi Santoso',
        'room_number' => 'A-01',
        'period_start' => now()->toDateString(),
        'period_end' => now()->addDays(30)->toDateString(),
    ]);

    $listener = app(BatalkanInvoiceSetelahJadwalBatal::class);
    $listener->handle(new JadwalBatal(
        scheduleId: 10,
        roomId: 1,
        roomNumber: 'A-01',
    ));

    // Invoice DP (paid) tidak berubah
    $this->assertDatabaseHas('invoices', ['id' => $dpInvoice->id, 'status' => 'paid']);

    // Invoice pelunasan (unpaid) dibatalkan
    $this->assertDatabaseHas('invoices', ['id' => $pelunasanInvoice->id, 'status' => 'cancelled']);
});
