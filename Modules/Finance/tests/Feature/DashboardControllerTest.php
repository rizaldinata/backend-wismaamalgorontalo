<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
});

// =========================================================
// 4.1 — KPI Summary (GET /api/finance/dashboard/kpi-summary)
// =========================================================

test('[BERHASIL] endpoint kpiSummary merespons 200 dengan struktur data yang lengkap', function () {
    $response = $this->getJson('/api/finance/dashboard/kpi-summary');

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Data KPI summary berhasil diambil'])
        ->assertJsonStructure([
            'data' => [
                'total_revenue_this_month',
                'revenue_monthly_rents',
                'revenue_daily_rents',
                'total_expense',
                'net_profit',
                'total_unpaid_invoices_amount',
                'total_overdue_amount',
                'overdue_invoices_count',
                'pending_verification_count',
            ],
        ]);
});

test('[BERHASIL] endpoint kpiSummary menerima filter bulan dan tahun', function () {
    $response = $this->getJson('/api/finance/dashboard/kpi-summary?month=1&year=2026');

    $response->assertOk()
        ->assertJsonStructure(['data' => ['total_revenue_this_month']]);
});

test('[BERHASIL] kpiSummary mengembalikan nol untuk semua metrik jika tidak ada data', function () {
    $response = $this->getJson('/api/finance/dashboard/kpi-summary');

    $data = $response->assertOk()->json('data');

    expect($data['total_revenue_this_month'])->toEqual(0);
    expect($data['net_profit'])->toEqual(0);
    expect($data['overdue_invoices_count'])->toBe(0);
    expect($data['pending_verification_count'])->toBe(0);
});

test('[BERHASIL] kpiSummary menghitung pending_verification_count secara akurat', function () {
    Payment::factory()->count(4)->create(['status' => PaymentStatus::PENDING]);

    $response = $this->getJson('/api/finance/dashboard/kpi-summary');

    $response->assertOk()
        ->assertJsonPath('data.pending_verification_count', 4);
});

// =========================================================
// 4.2 — Revenue Chart (GET /api/finance/dashboard/revenue-chart)
// =========================================================

test('[BERHASIL] endpoint revenueChart merespons 200 dengan struktur grafik', function () {
    $response = $this->getJson('/api/finance/dashboard/revenue-chart');

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Data grafik pendapatan 6 bulan terakhir'])
        ->assertJsonStructure([
            'data' => [
                'labels',
                'datasets',
            ],
        ]);
});

test('[BERHASIL] revenueChart mengembalikan tepat 6 label dan 3 dataset', function () {
    $response = $this->getJson('/api/finance/dashboard/revenue-chart');

    $response->assertOk();

    expect($response->json('data.labels'))->toHaveCount(6);
    expect($response->json('data.datasets'))->toHaveCount(3);
});

test('[BERHASIL] revenueChart menampilkan nilai nol saat tidak ada transaksi', function () {
    $response = $this->getJson('/api/finance/dashboard/revenue-chart');

    $response->assertOk();

    // Semua data di semua dataset harus bernilai 0 jika tidak ada invoice
    // json_encode(0.0) menghasilkan "0" (integer), bukan "0.0", jadi gunakan toEqual
    foreach ($response->json('data.datasets') as $dataset) {
        foreach ($dataset['data'] as $value) {
            expect($value)->toEqual(0);
        }
    }
});

// =========================================================
// 4.3 — Due Invoices Widget (GET /api/finance/dashboard/due-invoices)
// =========================================================

test('[BERHASIL] endpoint dueInvoices merespons 200 dengan pesan yang tepat', function () {
    $response = $this->getJson('/api/finance/dashboard/due-invoices');

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Data tagihan jatuh tempo berhasil diambil']);
});

test('[BERHASIL] dueInvoices menampilkan invoice yang jatuh tempo dalam 7 hari', function () {
    Invoice::factory()->count(2)->create([
        'status' => InvoiceStatus::UNPAID,
        'due_date' => now()->addDays(3),
    ]);
    // Tidak masuk — terlalu jauh
    Invoice::factory()->create([
        'status' => InvoiceStatus::UNPAID,
        'due_date' => now()->addDays(30),
    ]);

    $response = $this->getJson('/api/finance/dashboard/due-invoices');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

test('[BERHASIL] dueInvoices mengembalikan array kosong jika tidak ada tagihan jatuh tempo', function () {
    $response = $this->getJson('/api/finance/dashboard/due-invoices');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(0);
});

// =========================================================
// 4.4 — Pending Payments Widget (GET /api/finance/dashboard/pending-payments)
// =========================================================

test('[BERHASIL] endpoint pendingPayments merespons 200 dengan pesan yang tepat', function () {
    $response = $this->getJson('/api/finance/dashboard/pending-payments');

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Data pembayaran tertunda berhasil diambil']);
});

test('[BERHASIL] pendingPayments hanya menampilkan pembayaran berstatus pending', function () {
    Payment::factory()->count(3)->create(['status' => PaymentStatus::PENDING]);
    Payment::factory()->count(5)->create(['status' => PaymentStatus::VERIFIED]);

    $response = $this->getJson('/api/finance/dashboard/pending-payments');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

test('[BERHASIL] pendingPayments mengembalikan array kosong jika tidak ada yang pending', function () {
    Payment::factory()->count(2)->create(['status' => PaymentStatus::VERIFIED]);

    $response = $this->getJson('/api/finance/dashboard/pending-payments');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(0);
});
