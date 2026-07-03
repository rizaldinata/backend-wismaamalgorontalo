<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Enums\GuestBillStatus;
use Modules\Guest\Enums\GuestRelationship;
use Modules\Guest\Models\Guest;
use Modules\Guest\Models\GuestBill;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
});

// Helper: buat GuestBill dengan status tertentu
function buatGuestBill(string $status = 'unpaid'): GuestBill
{
    $guest = Guest::create([
        'name' => 'Tamu Admin Test',
        'check_in_at' => now()->subDays(3),
        'check_out_at' => now(),
        'relationship' => GuestRelationship::FRIEND,
        'total_days' => 3,
        'billable_days' => 1,
        'charge_amount' => 25000,
    ]);

    return GuestBill::create([
        'guest_id' => $guest->id,
        'bill_number' => 'GB-ADMIN-'.uniqid(),
        'amount' => 25000,
        'status' => $status,
    ]);
}

// =========================================================
// 6.6 — Daftar Semua Tagihan Tamu (GET /api/admin/guest-bills)
// =========================================================

test('[BERHASIL] admin dapat melihat semua tagihan tamu dengan paginasi', function () {
    buatGuestBill('unpaid');
    buatGuestBill('pending');
    buatGuestBill('verified');

    $response = $this->getJson('/api/admin/guest-bills');

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Daftar tagihan tamu berhasil diambil.']);

    // data dibungkus apiSuccess → data.data berisi records
    expect($response->json('data.data'))->toHaveCount(3);
});

test('[BERHASIL] daftar tagihan tamu kosong jika belum ada data', function () {
    $response = $this->getJson('/api/admin/guest-bills');

    $response->assertOk();
    expect($response->json('data.data'))->toHaveCount(0);
});

// =========================================================
// 6.7 — Verifikasi Tagihan Tamu (POST /api/admin/guest-bills/{id}/verify)
// =========================================================

test('[BERHASIL] admin dapat menyetujui tagihan pending → status menjadi VERIFIED', function () {
    $bill = buatGuestBill('pending');

    $response = $this->postJson("/api/admin/guest-bills/{$bill->id}/verify", [
        'is_approved' => true,
        'admin_notes' => 'Bukti transfer valid.',
    ]);

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Tagihan berhasil diverifikasi.'])
        ->assertJsonPath('data.status', GuestBillStatus::VERIFIED->value);

    expect($bill->fresh()->paid_at)->not->toBeNull();
});

test('[BERHASIL] admin dapat menolak tagihan pending → status menjadi REJECTED', function () {
    $bill = buatGuestBill('pending');

    $response = $this->postJson("/api/admin/guest-bills/{$bill->id}/verify", [
        'is_approved' => false,
        'admin_notes' => 'Bukti tidak jelas.',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', GuestBillStatus::REJECTED->value);

    expect($bill->fresh()->admin_notes)->toBe('Bukti tidak jelas.');
});

test('[GAGAL] verify mengembalikan 404 jika tagihan tidak ditemukan', function () {
    $response = $this->postJson('/api/admin/guest-bills/9999/verify', [
        'is_approved' => true,
    ]);

    $response->assertNotFound();
});

test('[GAGAL] verify mengembalikan 422 jika tagihan tidak berstatus pending', function () {
    $bill = buatGuestBill('verified');

    $response = $this->postJson("/api/admin/guest-bills/{$bill->id}/verify", [
        'is_approved' => true,
    ]);

    $response->assertStatus(422);
});

test('[GAGAL] validasi gagal jika is_approved tidak disertakan', function () {
    $bill = buatGuestBill('pending');

    $response = $this->postJson("/api/admin/guest-bills/{$bill->id}/verify", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['is_approved']);
});
