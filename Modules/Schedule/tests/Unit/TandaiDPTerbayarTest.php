<?php

use App\Events\Finance\PembayaranDiverifikasi;
use App\Events\Jadwal\DPDibayar;
use App\Events\Jadwal\JadwalSewaAktif;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Schedule\Enums\SchedulePaymentScheme;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Enums\ScheduleType;
use Modules\Schedule\Listeners\AktifkanJadwalSetelahPelunasan;
use Modules\Schedule\Listeners\AktifkanJadwalSetelahPembayaranDiverifikasi;
use Modules\Schedule\Listeners\TandaiDPTerbayarSetelahPembayaranDP;
use Modules\Schedule\Models\Schedule;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function buatInvoiceDP(int $scheduleId, string $type = 'dp'): Invoice
{
    return Invoice::create([
        'schedule_id'    => $scheduleId,
        'type'           => $type,
        'invoice_number' => 'DP-TEST-' . $scheduleId,
        'amount'         => 500000,
        'status'         => InvoiceStatus::UNPAID->value,
        'due_date'       => now()->addDays(10)->toDateString(),
        'tenant_name'    => 'Budi Santoso',
        'tenant_phone'   => '08123456789',
        'room_number'    => 'A-01',
        'period_start'   => now()->addDays(8)->toDateString(),
        'period_end'     => now()->addDays(38)->toDateString(),
    ]);
}

function buatEventVerifikasi(int $invoiceId, int $scheduleId): PembayaranDiverifikasi
{
    return new PembayaranDiverifikasi(
        paymentId:     1,
        invoiceId:     $invoiceId,
        scheduleId:    $scheduleId,
        amount:        500000.0,
        tenantName:    'Budi Santoso',
        tenantPhone:   '08123456789',
        invoiceNumber: 'DP-TEST-' . $scheduleId,
        roomTitle:     'Kamar Standar',
        roomNumber:    'A-01',
        startDate:     now()->addDays(8)->toDateString(),
        endDate:       now()->addDays(38)->toDateString(),
    );
}

test('[BERHASIL] TandaiDPTerbayarSetelahPembayaranDP mengubah status ke dp_terbayar dan fires DPDibayar', function () {
    Event::fake([DPDibayar::class]);

    $schedule = Schedule::create([
        'room_id'        => 1,
        'type'           => ScheduleType::SEWA->value,
        'status'         => ScheduleStatus::PENDING->value,
        'payment_scheme' => SchedulePaymentScheme::DP->value,
        'dp_amount'      => 500000,
        'start_date'     => now()->addDays(8)->toDateString(),
        'end_date'       => now()->addDays(38)->toDateString(),
        'agreed_price'   => 1000000,
        'tenant_name'    => 'Budi Santoso',
        'tenant_phone'   => '08123456789',
    ]);

    $invoice  = buatInvoiceDP($schedule->id, 'dp');
    $listener = app(TandaiDPTerbayarSetelahPembayaranDP::class);
    $listener->handle(buatEventVerifikasi($invoice->id, $schedule->id));

    $schedule->refresh();
    expect($schedule->status)->toBe(ScheduleStatus::DP_TERBAYAR);
    expect($schedule->dp_paid_at)->not->toBeNull();

    Event::assertDispatched(DPDibayar::class, function ($e) use ($schedule) {
        return $e->scheduleId === $schedule->id
            && $e->dpAmount === 500000.0
            && $e->pelunasanAmount === 500000.0;
    });
});

test('[DIABAIKAN] TandaiDPTerbayarSetelahPembayaranDP tidak melakukan apapun jika invoice type=sewa', function () {
    Event::fake([DPDibayar::class]);

    $schedule = Schedule::create([
        'room_id'    => 2,
        'type'       => ScheduleType::SEWA->value,
        'status'     => ScheduleStatus::PENDING->value,
        'start_date' => now()->addDays(8)->toDateString(),
        'end_date'   => now()->addDays(38)->toDateString(),
    ]);

    $invoice  = buatInvoiceDP($schedule->id, 'sewa');
    $listener = app(TandaiDPTerbayarSetelahPembayaranDP::class);
    $listener->handle(buatEventVerifikasi($invoice->id, $schedule->id));

    $schedule->refresh();
    expect($schedule->status)->toBe(ScheduleStatus::PENDING);
    Event::assertNotDispatched(DPDibayar::class);
});

test('[BERHASIL] AktifkanJadwalSetelahPelunasan mengaktifkan langsung jika start_date sudah tiba', function () {
    Event::fake([JadwalSewaAktif::class]);

    $schedule = Schedule::create([
        'room_id'        => 3,
        'type'           => ScheduleType::SEWA->value,
        'status'         => ScheduleStatus::DP_TERBAYAR->value,
        'payment_scheme' => SchedulePaymentScheme::DP->value,
        'dp_amount'      => 500000,
        'dp_paid_at'     => now()->subHour(),
        'start_date'     => now()->subDay()->toDateString(), // kemarin → sudah tiba
        'end_date'       => now()->addDays(30)->toDateString(),
        'agreed_price'   => 1000000,
        'tenant_name'    => 'Budi Santoso',
    ]);

    $invoice = Invoice::create([
        'schedule_id'    => $schedule->id,
        'type'           => 'pelunasan',
        'invoice_number' => 'PLN-TEST-' . $schedule->id,
        'amount'         => 500000,
        'status'         => InvoiceStatus::UNPAID->value,
        'due_date'       => now()->toDateString(),
        'tenant_name'    => 'Budi Santoso',
        'room_number'    => 'A-01',
        'period_start'   => now()->subDay()->toDateString(),
        'period_end'     => now()->addDays(30)->toDateString(),
    ]);

    $listener = app(AktifkanJadwalSetelahPelunasan::class);
    $listener->handle(buatEventVerifikasi($invoice->id, $schedule->id));

    $schedule->refresh();
    expect($schedule->status)->toBe(ScheduleStatus::ACTIVE);
    Event::assertDispatched(JadwalSewaAktif::class);
});

test('[BERHASIL] AktifkanJadwalSetelahPelunasan mengkonfirmasi jika start_date belum tiba', function () {
    Event::fake([JadwalSewaAktif::class]);

    $schedule = Schedule::create([
        'room_id'        => 30,
        'type'           => ScheduleType::SEWA->value,
        'status'         => ScheduleStatus::DP_TERBAYAR->value,
        'payment_scheme' => SchedulePaymentScheme::DP->value,
        'dp_amount'      => 500000,
        'dp_paid_at'     => now()->subHour(),
        'start_date'     => now()->addDays(5)->toDateString(), // masa depan
        'end_date'       => now()->addDays(35)->toDateString(),
        'agreed_price'   => 1000000,
        'tenant_name'    => 'Budi Santoso',
    ]);

    $invoice = Invoice::create([
        'schedule_id'    => $schedule->id,
        'type'           => 'pelunasan',
        'invoice_number' => 'PLN-TEST-FUTURE-' . $schedule->id,
        'amount'         => 500000,
        'status'         => InvoiceStatus::UNPAID->value,
        'due_date'       => now()->addDays(5)->toDateString(),
        'tenant_name'    => 'Budi Santoso',
        'room_number'    => 'A-01',
        'period_start'   => now()->addDays(5)->toDateString(),
        'period_end'     => now()->addDays(35)->toDateString(),
    ]);

    $listener = app(AktifkanJadwalSetelahPelunasan::class);
    $listener->handle(buatEventVerifikasi($invoice->id, $schedule->id));

    $schedule->refresh();
    expect($schedule->status)->toBe(ScheduleStatus::TERKONFIRMASI);
    Event::assertNotDispatched(JadwalSewaAktif::class);
});

test('[DIABAIKAN] AktifkanJadwalSetelahPembayaranDiverifikasi melewati invoice type=dp', function () {
    Event::fake([JadwalSewaAktif::class]);

    $schedule = Schedule::create([
        'room_id'        => 4,
        'type'           => ScheduleType::SEWA->value,
        'status'         => ScheduleStatus::PENDING->value,
        'payment_scheme' => SchedulePaymentScheme::DP->value,
        'start_date'     => now()->addDays(8)->toDateString(),
        'end_date'       => now()->addDays(38)->toDateString(),
    ]);

    $invoice  = buatInvoiceDP($schedule->id, 'dp');
    $listener = app(AktifkanJadwalSetelahPembayaranDiverifikasi::class);
    $listener->handle(buatEventVerifikasi($invoice->id, $schedule->id));

    $schedule->refresh();
    expect($schedule->status)->toBe(ScheduleStatus::PENDING);
    Event::assertNotDispatched(JadwalSewaAktif::class);
});
