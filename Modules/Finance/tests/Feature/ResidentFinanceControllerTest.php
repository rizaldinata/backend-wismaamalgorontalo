<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
    $this->penghuni = User::factory()->create(['name' => 'Budi Santoso']);
});

// =========================================================
// 5.1 — Ringkasan Keuangan (GET /api/finance/me/summary)
// =========================================================

test('[BERHASIL] penghuni dapat melihat ringkasan keuangan dengan struktur lengkap', function () {
    $response = $this->actingAs($this->penghuni)
        ->getJson('/api/finance/me/summary');

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Ringkasan keuangan berhasil diambil'])
        ->assertJsonStructure([
            'data' => [
                'resident_name',
                'active_leases',
                'total_unpaid',
                'unpaid_count',
            ],
        ]);
});

test('[BERHASIL] active_leases bernilai array kosong jika tidak ada data di finance_active_tenants', function () {
    $response = $this->actingAs($this->penghuni)
        ->getJson('/api/finance/me/summary');

    $response->assertOk()
        ->assertJsonPath('data.active_leases', [])
        ->assertJsonPath('data.resident_name', 'Budi Santoso');
});

test('[BERHASIL] active_leases terisi jika penghuni terdaftar di finance_active_tenants', function () {
    DB::table('finance_active_tenants')->insert([
        'schedule_id' => 10,
        'user_id'     => $this->penghuni->id,
        'room_number' => '101',
        'tenant_name' => 'Budi Santoso',
        'end_date'    => now()->addMonths(3)->toDateString(),
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $response = $this->actingAs($this->penghuni)
        ->getJson('/api/finance/me/summary');

    $response->assertOk();
    $activeLeases = $response->json('data.active_leases');
    expect($activeLeases)->toBeArray()->toHaveCount(1);
    expect($activeLeases[0]['room_number'])->toBe('101');
    expect($activeLeases[0]['id'])->toBe(10);
});

test('[BERHASIL] active_leases mengembalikan semua sewa aktif jika penghuni punya lebih dari satu', function () {
    DB::table('finance_active_tenants')->insert([
        ['schedule_id' => 10, 'user_id' => $this->penghuni->id, 'room_number' => '101', 'tenant_name' => 'Budi Santoso', 'end_date' => now()->addMonths(3)->toDateString(), 'created_at' => now(), 'updated_at' => now()],
        ['schedule_id' => 20, 'user_id' => $this->penghuni->id, 'room_number' => '202', 'tenant_name' => 'Budi Santoso', 'end_date' => now()->addMonths(6)->toDateString(), 'created_at' => now(), 'updated_at' => now()],
    ]);

    $response = $this->actingAs($this->penghuni)
        ->getJson('/api/finance/me/summary');

    $response->assertOk();
    $activeLeases = $response->json('data.active_leases');
    expect($activeLeases)->toBeArray()->toHaveCount(2);
});

test('[BERHASIL] total_unpaid menghitung akumulasi tagihan belum bayar milik penghuni', function () {
    Invoice::factory()->create([
        'tenant_user_id' => $this->penghuni->id,
        'status'         => InvoiceStatus::UNPAID,
        'amount'         => 400000,
    ]);
    Invoice::factory()->create([
        'tenant_user_id' => $this->penghuni->id,
        'status'         => InvoiceStatus::UNPAID,
        'amount'         => 600000,
    ]);
    // invoice paid — tidak masuk kalkulasi
    Invoice::factory()->create([
        'tenant_user_id' => $this->penghuni->id,
        'status'         => InvoiceStatus::PAID,
        'amount'         => 999999,
    ]);

    $response = $this->actingAs($this->penghuni)
        ->getJson('/api/finance/me/summary');

    $response->assertOk();
    expect($response->json('data.total_unpaid'))->toEqual(1000000);
    expect($response->json('data.unpaid_count'))->toBe(2);
});

test('[BERHASIL] summary tidak menghitung tagihan milik penghuni lain', function () {
    $penghuniLain = User::factory()->create();
    Invoice::factory()->create([
        'tenant_user_id' => $penghuniLain->id,
        'status'         => InvoiceStatus::UNPAID,
        'amount'         => 500000,
    ]);
    Invoice::factory()->create([
        'tenant_user_id' => $this->penghuni->id,
        'status'         => InvoiceStatus::UNPAID,
        'amount'         => 200000,
    ]);

    $response = $this->actingAs($this->penghuni)
        ->getJson('/api/finance/me/summary');

    $response->assertOk();
    expect($response->json('data.total_unpaid'))->toEqual(200000);
    expect($response->json('data.unpaid_count'))->toBe(1);
});

// =========================================================
// 5.2 — Daftar Tagihan Penghuni (GET /api/finance/me/invoices)
// =========================================================

test('[BERHASIL] penghuni hanya melihat tagihan milik mereka sendiri', function () {
    $penghuniLain = User::factory()->create();
    Invoice::factory()->count(3)->create(['tenant_user_id' => $this->penghuni->id]);
    Invoice::factory()->count(5)->create(['tenant_user_id' => $penghuniLain->id]);

    $response = $this->actingAs($this->penghuni)
        ->getJson('/api/finance/me/invoices');

    $response->assertOk()
        ->assertJsonFragment(['success' => true])
        ->assertJsonFragment(['message' => 'Daftar tagihan Anda berhasil diambil']);

    expect($response->json('data'))->toHaveCount(3);
});

test('[BERHASIL] daftar tagihan dapat difilter berdasarkan status', function () {
    Invoice::factory()->count(2)->create([
        'tenant_user_id' => $this->penghuni->id,
        'status'         => InvoiceStatus::UNPAID,
    ]);
    Invoice::factory()->create([
        'tenant_user_id' => $this->penghuni->id,
        'status'         => InvoiceStatus::PAID,
    ]);

    $response = $this->actingAs($this->penghuni)
        ->getJson('/api/finance/me/invoices?status=unpaid');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

test('[BERHASIL] mengembalikan list kosong jika penghuni tidak memiliki tagihan', function () {
    $response = $this->actingAs($this->penghuni)
        ->getJson('/api/finance/me/invoices');

    $response->assertOk();
    expect($response->json('data'))->toBeArray()->toHaveCount(0);
});

// =========================================================
// 5.3 — Detail Tagihan Penghuni (GET /api/finance/me/invoices/{id})
// =========================================================

test('[BERHASIL] penghuni dapat melihat detail tagihan milik mereka', function () {
    $invoice = Invoice::factory()->create([
        'tenant_user_id' => $this->penghuni->id,
        'amount'         => 750000,
        'status'         => InvoiceStatus::UNPAID,
    ]);

    $response = $this->actingAs($this->penghuni)
        ->getJson("/api/finance/me/invoices/{$invoice->id}");

    $response->assertOk()
        ->assertJsonFragment(['success' => true])
        ->assertJsonFragment(['message' => 'Detail tagihan berhasil diambil'])
        ->assertJsonPath('data.id', $invoice->id);
});

test('[GAGAL] showInvoice mengembalikan 404 jika invoice tidak ditemukan', function () {
    $response = $this->actingAs($this->penghuni)
        ->getJson('/api/finance/me/invoices/9999');

    $response->assertNotFound();
});

test('[GAGAL] penghuni tidak bisa melihat detail tagihan milik penghuni lain', function () {
    $penghuniLain = User::factory()->create();
    $invoiceLain  = Invoice::factory()->create(['tenant_user_id' => $penghuniLain->id]);

    $response = $this->actingAs($this->penghuni)
        ->getJson("/api/finance/me/invoices/{$invoiceLain->id}");

    $response->assertNotFound();
});

// =========================================================
// 5.4 — Riwayat Pembayaran Penghuni (GET /api/finance/me/payments)
// =========================================================

test('[BERHASIL] penghuni dapat melihat riwayat pembayaran mereka', function () {
    $invoice = Invoice::factory()->create([
        'tenant_user_id' => $this->penghuni->id,
        'schedule_id'    => 5,
    ]);
    Payment::factory()->count(2)->create([
        'invoice_id' => $invoice->id,
        'status'     => PaymentStatus::VERIFIED,
    ]);

    $response = $this->actingAs($this->penghuni)
        ->getJson('/api/finance/me/payments');

    $response->assertOk()
        ->assertJsonFragment(['success' => true])
        ->assertJsonFragment(['message' => 'Riwayat pembayaran Anda berhasil diambil']);

    expect($response->json('data'))->toHaveCount(2);
});

test('[BERHASIL] penghuni tidak melihat pembayaran milik penghuni lain', function () {
    $penghuniLain  = User::factory()->create();
    $invoiceSaya   = Invoice::factory()->create(['tenant_user_id' => $this->penghuni->id, 'schedule_id' => 10]);
    $invoiceMereka = Invoice::factory()->create(['tenant_user_id' => $penghuniLain->id,  'schedule_id' => 20]);

    Payment::factory()->count(2)->create(['invoice_id' => $invoiceSaya->id]);
    Payment::factory()->count(5)->create(['invoice_id' => $invoiceMereka->id]);

    $response = $this->actingAs($this->penghuni)
        ->getJson('/api/finance/me/payments');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

test('[GAGAL] mengembalikan 404 jika penghuni tidak memiliki invoice sama sekali', function () {
    $response = $this->actingAs($this->penghuni)
        ->getJson('/api/finance/me/payments');

    $response->assertNotFound();
});
