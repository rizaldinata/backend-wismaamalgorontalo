<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Room\Models\Room;
use Modules\Schedule\Enums\ScheduleStatus;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();

    $this->admin  = User::factory()->create(['name' => 'Admin']);
    $this->tenant = User::factory()->create(['name' => 'Budi']);
    $this->room   = Room::factory()->create(['price' => 1_000_000]);
});

// ── Helper: buat dan verifikasi payment manual untuk sebuah invoice ──────────

function verifikasiPembayaranFlow(TestCase $test, Invoice $invoice): void
{
    $payment = Payment::create([
        'invoice_id'     => $invoice->id,
        'payment_method' => 'manual',
        'status'         => PaymentStatus::PENDING,
        'amount'         => $invoice->amount,
    ]);

    $test->postJson("/api/finance/payments/{$payment->id}/verify", [
        'is_approved' => true,
        'admin_notes' => 'Test verified',
    ])->assertOk();
}

// =========================================================
// Alur Penuh DP
// =========================================================

test('[BERHASIL] alur penuh DP: booking → DP dibayar → pelunasan dibuat → pelunasan dibayar → jadwal active', function () {
    $startDate = now()->addDays(15)->toDateString();
    $endDate   = now()->addDays(45)->toDateString();

    // 1. Buat booking dengan payment_scheme=dp
    $response = $this->actingAs($this->tenant)
        ->postJson('/api/v1/room-schedules', [
            'room_id'        => $this->room->id,
            'type'           => 'sewa',
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'agreed_price'   => 1_000_000,
            'payment_scheme' => 'dp',
            'tenant_name'    => 'Budi',
            'tenant_phone'   => '08123456789',
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['status' => true]);

    $scheduleId = $response->json('data.id');

    // 2. Invoice DP harus otomatis dibuat
    $invoiceDP = Invoice::where('schedule_id', $scheduleId)
        ->where('type', 'dp')
        ->first();

    expect($invoiceDP)->not->toBeNull();
    expect($invoiceDP->amount)->toEqual(500_000.0); // 50% dari 1.000.000
    expect($invoiceDP->status)->toBe(InvoiceStatus::UNPAID);

    // 3. Bayar & verifikasi invoice DP
    verifikasiPembayaranFlow($this, $invoiceDP);

    // 4. Schedule harus berpindah ke dp_terbayar
    $schedule = DB::table('room_schedules')->find($scheduleId);
    expect($schedule->status)->toBe(ScheduleStatus::DP_TERBAYAR->value);
    expect($schedule->dp_paid_at)->not->toBeNull();

    // 5. Invoice pelunasan harus otomatis dibuat
    $invoicePelunasan = Invoice::where('schedule_id', $scheduleId)
        ->where('type', 'pelunasan')
        ->first();

    expect($invoicePelunasan)->not->toBeNull();
    expect($invoicePelunasan->amount)->toEqual(500_000.0); // sisa 50%
    expect($invoicePelunasan->status)->toBe(InvoiceStatus::UNPAID);
    expect($invoicePelunasan->due_date->toDateString())->toBe($startDate);

    // 6. Bayar & verifikasi invoice pelunasan
    verifikasiPembayaranFlow($this, $invoicePelunasan);

    // 7. Schedule harus berpindah ke active atau terkonfirmasi
    $schedule = DB::table('room_schedules')->find($scheduleId);
    expect($schedule->status)->toBeIn([
        ScheduleStatus::ACTIVE->value,
        ScheduleStatus::TERKONFIRMASI->value,
    ]);
});

// =========================================================
// dp_refund_eligible
// =========================================================

test('[BERHASIL] dp_refund_eligible = true jika cancel > 3 hari sebelum start_date', function () {
    // start_date 10 hari ke depan → eligible
    $schedule = DB::table('room_schedules')->insertGetId([
        'room_id'        => $this->room->id,
        'type'           => 'sewa',
        'status'         => ScheduleStatus::DP_TERBAYAR->value,
        'payment_scheme' => 'dp',
        'dp_amount'      => 500_000,
        'dp_paid_at'     => now()->subMinutes(10),
        'start_date'     => now()->addDays(10)->toDateString(),
        'end_date'       => now()->addDays(40)->toDateString(),
        'agreed_price'   => 1_000_000,
        'tenant_name'    => 'Budi',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson("/api/v1/room-schedules/{$schedule}");

    $response->assertOk()
        ->assertJsonPath('data.dp_refund_eligible', true);
});

test('[BERHASIL] dp_refund_eligible = false jika cancel <= 3 hari sebelum start_date', function () {
    // start_date 2 hari ke depan → tidak eligible
    $schedule = DB::table('room_schedules')->insertGetId([
        'room_id'        => $this->room->id,
        'type'           => 'sewa',
        'status'         => ScheduleStatus::DP_TERBAYAR->value,
        'payment_scheme' => 'dp',
        'dp_amount'      => 500_000,
        'dp_paid_at'     => now()->subMinutes(10),
        'start_date'     => now()->addDays(2)->toDateString(),
        'end_date'       => now()->addDays(32)->toDateString(),
        'agreed_price'   => 1_000_000,
        'tenant_name'    => 'Budi',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson("/api/v1/room-schedules/{$schedule}");

    $response->assertOk()
        ->assertJsonPath('data.dp_refund_eligible', false);
});

test('[BERHASIL] dp_refund_eligible = null jika jadwal bukan dp_terbayar', function () {
    $schedule = DB::table('room_schedules')->insertGetId([
        'room_id'        => $this->room->id,
        'type'           => 'sewa',
        'status'         => ScheduleStatus::ACTIVE->value,
        'payment_scheme' => 'full',
        'start_date'     => now()->toDateString(),
        'end_date'       => now()->addDays(30)->toDateString(),
        'agreed_price'   => 1_000_000,
        'tenant_name'    => 'Budi',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson("/api/v1/room-schedules/{$schedule}");

    $response->assertOk()
        ->assertJsonPath('data.dp_refund_eligible', null);
});
