<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;
use Modules\Guest\Enums\GuestBillStatus;
use Modules\Guest\Enums\GuestRelationship;
use Modules\Guest\Models\Guest;
use Modules\Guest\Models\GuestActiveContext;
use Modules\Guest\Models\GuestBill;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
    $this->penghuni = User::factory()->create();
});

// Helper: buat Guest + GuestBill lengkap dengan ownership via GuestActiveContext (untuk show)
function buatGuestDenganKonteks(User $user, string $billStatus = 'unpaid'): array
{
    $scheduleId = 10;

    GuestActiveContext::create([
        'user_id'     => $user->id,
        'schedule_id' => $scheduleId,
        'room_id'     => 1,
        'room_price'  => 500000,
        'tenant_name' => $user->name,
        'is_active'   => true,
    ]);

    $guest = Guest::create([
        'user_id'              => $user->id,
        'schedule_reference_id' => $scheduleId,
        'name'                 => 'Tamu Uji',
        'check_in_at'          => now()->subDays(5),
        'check_out_at'         => now(),
        'relationship'         => GuestRelationship::FRIEND,
        'total_days'           => 5,
        'billable_days'        => 3,
        'charge_amount'        => 75000,
    ]);

    $bill = GuestBill::create([
        'guest_id'    => $guest->id,
        'bill_number' => 'GB-TEST-001',
        'amount'      => 75000,
        'status'      => $billStatus,
    ]);

    return compact('guest', 'bill');
}

// Helper: buat Guest milik user (untuk pay — cek via user_id, bukan konteks)
function buatGuestMilikUser(User $user, string $billStatus = 'unpaid'): array
{
    $guest = Guest::create([
        'user_id'      => $user->id,
        'name'         => 'Tamu Bayar',
        'check_in_at'  => now()->subDays(4),
        'check_out_at' => now(),
        'relationship' => GuestRelationship::SIBLING,
        'total_days'   => 4,
        'billable_days' => 2,
        'charge_amount' => 50000,
    ]);

    $bill = GuestBill::create([
        'guest_id'    => $guest->id,
        'bill_number' => 'GB-PAY-001',
        'amount'      => 50000,
        'status'      => $billStatus,
    ]);

    return compact('guest', 'bill');
}

// =========================================================
// 6.3 — Lihat Tagihan Tamu (GET /api/guests/{guestId}/bill)
// =========================================================

test('[BERHASIL] penghuni dapat melihat tagihan tamu yang dimilikinya', function () {
    ['guest' => $guest, 'bill' => $bill] = buatGuestDenganKonteks($this->penghuni);

    $response = $this->actingAs($this->penghuni)
        ->getJson("/api/guests/{$guest->id}/bill");

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Data tagihan berhasil diambil.'])
        ->assertJsonPath('data.id', $bill->id)
        ->assertJsonPath('data.bill_number', 'GB-TEST-001');
});

test('[GAGAL] mengembalikan 403 jika penghuni tidak memiliki konteks sewa aktif', function () {
    $guest = Guest::create([
        'name'         => 'Tamu Asing',
        'check_in_at'  => now()->subDay(),
        'check_out_at' => now(),
        'relationship' => GuestRelationship::OTHER,
        'total_days'   => 1,
        'billable_days' => 0,
        'charge_amount' => 0,
    ]);

    $response = $this->actingAs($this->penghuni)
        ->getJson("/api/guests/{$guest->id}/bill");

    $response->assertForbidden();
});

test('[GAGAL] mengembalikan 404 jika tagihan tidak ditemukan untuk tamu', function () {
    // Konteks aktif ada, tamu ada, tapi tidak ada GuestBill
    $scheduleId = 20;
    GuestActiveContext::create([
        'user_id'     => $this->penghuni->id,
        'schedule_id' => $scheduleId,
        'room_id'     => 2,
        'room_price'  => 300000,
        'tenant_name' => 'Penghuni Uji',
        'is_active'   => true,
    ]);
    $guest = Guest::create([
        'user_id'              => $this->penghuni->id,
        'schedule_reference_id' => $scheduleId,
        'name'                 => 'Tamu Tanpa Tagihan',
        'check_in_at'          => now()->subDay(),
        'check_out_at'         => now(),
        'relationship'         => GuestRelationship::RELATIVE,
        'total_days'           => 1,
        'billable_days'        => 0,
        'charge_amount'        => 0,
    ]);

    $response = $this->actingAs($this->penghuni)
        ->getJson("/api/guests/{$guest->id}/bill");

    $response->assertNotFound();
});

// =========================================================
// 6.4 — Bayar Tagihan Manual (POST /api/guests/{guestId}/bill/pay)
// =========================================================

test('[BERHASIL] penghuni dapat membayar tagihan tamu dengan bukti transfer manual', function () {
    Storage::fake('public');
    ['guest' => $guest] = buatGuestMilikUser($this->penghuni, 'unpaid');

    $response = $this->actingAs($this->penghuni)
        ->postJson("/api/guests/{$guest->id}/bill/pay", [
            'payment_method' => 'manual',
            'payment_proof'  => UploadedFile::fake()->image('bukti.jpg'),
        ]);

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Pembayaran berhasil diproses.'])
        ->assertJsonPath('data.status', GuestBillStatus::PENDING->value);
});

test('[GAGAL] mengembalikan 422 jika tagihan sudah diverifikasi dan tidak bisa dibayar ulang', function () {
    Storage::fake('public');
    ['guest' => $guest] = buatGuestMilikUser($this->penghuni, 'verified');

    $response = $this->actingAs($this->penghuni)
        ->postJson("/api/guests/{$guest->id}/bill/pay", [
            'payment_method' => 'manual',
            'payment_proof'  => UploadedFile::fake()->image('bukti.jpg'),
        ]);

    $response->assertStatus(422);
});

test('[GAGAL] validasi gagal jika bukti pembayaran tidak disertakan untuk metode manual', function () {
    ['guest' => $guest] = buatGuestMilikUser($this->penghuni);

    $response = $this->actingAs($this->penghuni)
        ->postJson("/api/guests/{$guest->id}/bill/pay", [
            'payment_method' => 'manual',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['payment_proof']);
});

test('[GAGAL] validasi gagal jika payment_method tidak valid', function () {
    ['guest' => $guest] = buatGuestMilikUser($this->penghuni);

    $response = $this->actingAs($this->penghuni)
        ->postJson("/api/guests/{$guest->id}/bill/pay", [
            'payment_method' => 'transfer_bank',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['payment_method']);
});

// =========================================================
// 6.5 — Midtrans Webhook (POST /api/guests/bills/midtrans/notification)
// =========================================================

test('[BERHASIL] webhook settlement mengubah status tagihan tamu menjadi PAID', function () {
    ['guest' => $guest, 'bill' => $bill] = buatGuestMilikUser($this->penghuni, 'pending');
    $bill->update(['transaction_id' => 'GB-TRX-999']);

    $this->postJson('/api/guests/bills/midtrans/notification', [
        'order_id'           => 'GB-TRX-999',
        'transaction_status' => 'settlement',
    ])->assertOk();

    expect($bill->fresh()->status)->toBe(GuestBillStatus::PAID);
    expect($bill->fresh()->paid_at)->not->toBeNull();
});

test('[BERHASIL] webhook expire mengubah status tagihan tamu menjadi FAILED', function () {
    ['guest' => $guest, 'bill' => $bill] = buatGuestMilikUser($this->penghuni, 'pending');
    $bill->update(['transaction_id' => 'GB-TRX-888']);

    $this->postJson('/api/guests/bills/midtrans/notification', [
        'order_id'           => 'GB-TRX-888',
        'transaction_status' => 'expire',
    ])->assertOk();

    expect($bill->fresh()->status)->toBe(GuestBillStatus::FAILED);
});

test('[BERHASIL] webhook dengan transaction_id tidak dikenal diabaikan tanpa error', function () {
    $this->postJson('/api/guests/bills/midtrans/notification', [
        'order_id'           => 'GB-TRX-TIDAK-ADA',
        'transaction_status' => 'settlement',
    ])->assertOk();
});
