<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Services\FinanceDashboardService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// =========================================================
// 4.1 — getKpiSummary
// =========================================================

test('[BERHASIL] kpiSummary mengembalikan semua key yang dibutuhkan', function () {
    $service = app(FinanceDashboardService::class);

    $result = $service->getKpiSummary();

    expect($result)->toHaveKeys([
        'total_revenue_this_month',
        'revenue_monthly_rents',
        'revenue_daily_rents',
        'total_expense',
        'net_profit',
        'total_unpaid_invoices_amount',
        'total_overdue_amount',
        'overdue_invoices_count',
        'pending_verification_count',
    ]);
});

test('[BERHASIL] kpiSummary menghitung total_revenue dari invoice paid bulan ini', function () {
    Invoice::factory()->create(['status' => InvoiceStatus::PAID, 'amount' => 500000, 'updated_at' => now()]);
    Invoice::factory()->create(['status' => InvoiceStatus::PAID, 'amount' => 300000, 'updated_at' => now()]);
    Invoice::factory()->create(['status' => InvoiceStatus::UNPAID, 'amount' => 200000]);

    $result = app(FinanceDashboardService::class)->getKpiSummary();

    expect($result['total_revenue_this_month'])->toBe(800000.0);
});

test('[BERHASIL] kpiSummary menghitung net_profit sebagai selisih revenue dan expense', function () {
    Invoice::factory()->create(['status' => InvoiceStatus::PAID, 'amount' => 1000000, 'updated_at' => now()]);
    Expense::factory()->create(['amount' => 200000, 'expense_date' => now()->toDateString()]);

    $result = app(FinanceDashboardService::class)->getKpiSummary();

    expect($result['net_profit'])->toBe(800000.0);
});

test('[BERHASIL] kpiSummary menghitung net_profit negatif jika pengeluaran lebih besar', function () {
    Invoice::factory()->create(['status' => InvoiceStatus::PAID, 'amount' => 100000, 'updated_at' => now()]);
    Expense::factory()->create(['amount' => 500000, 'expense_date' => now()->toDateString()]);

    $result = app(FinanceDashboardService::class)->getKpiSummary();

    expect($result['net_profit'])->toBe(-400000.0);
});

test('[BERHASIL] kpiSummary menghitung total_unpaid_invoices_amount dari invoice belum bayar', function () {
    Invoice::factory()->create(['status' => InvoiceStatus::UNPAID, 'amount' => 600000]);
    Invoice::factory()->create(['status' => InvoiceStatus::UNPAID, 'amount' => 400000]);
    Invoice::factory()->create(['status' => InvoiceStatus::PAID,   'amount' => 999999]);

    $result = app(FinanceDashboardService::class)->getKpiSummary();

    expect($result['total_unpaid_invoices_amount'])->toBe(1000000.0);
});

test('[BERHASIL] kpiSummary menghitung overdue_invoices_count dari invoice terlambat bayar', function () {
    // overdue: unpaid + due_date sudah lewat
    Invoice::factory()->create(['status' => InvoiceStatus::UNPAID, 'due_date' => now()->subDays(5)]);
    Invoice::factory()->create(['status' => InvoiceStatus::UNPAID, 'due_date' => now()->subDays(1)]);
    // belum jatuh tempo — tidak dihitung
    Invoice::factory()->create(['status' => InvoiceStatus::UNPAID, 'due_date' => now()->addDays(3)]);

    $result = app(FinanceDashboardService::class)->getKpiSummary();

    expect($result['overdue_invoices_count'])->toBe(2);
});

test('[BERHASIL] kpiSummary menghitung pending_verification_count dari payment pending', function () {
    Payment::factory()->count(3)->create(['status' => PaymentStatus::PENDING]);
    Payment::factory()->count(2)->create(['status' => PaymentStatus::VERIFIED]);

    $result = app(FinanceDashboardService::class)->getKpiSummary();

    expect($result['pending_verification_count'])->toBe(3);
});

test('[BERHASIL] kpiSummary dapat difilter per bulan dan tahun tertentu', function () {
    // Invoice paid di bulan Januari 2026
    Invoice::factory()->create([
        'status'     => InvoiceStatus::PAID,
        'amount'     => 750000,
        'updated_at' => Carbon::create(2026, 1, 15),
    ]);
    // Invoice paid di bulan sekarang — tidak termasuk di filter Januari 2026
    Invoice::factory()->create([
        'status'     => InvoiceStatus::PAID,
        'amount'     => 999999,
        'updated_at' => now(),
    ]);

    $result = app(FinanceDashboardService::class)->getKpiSummary(month: 1, year: 2026);

    expect($result['total_revenue_this_month'])->toBe(750000.0);
});

test('[BERHASIL] kpiSummary mengembalikan nol jika tidak ada transaksi', function () {
    $result = app(FinanceDashboardService::class)->getKpiSummary();

    expect($result['total_revenue_this_month'])->toBe(0.0);
    expect($result['total_expense'])->toBe(0.0);
    expect($result['net_profit'])->toBe(0.0);
    expect($result['overdue_invoices_count'])->toBe(0);
    expect($result['pending_verification_count'])->toBe(0);
});

// =========================================================
// 4.2 — getRevenueChartData
// =========================================================

test('[BERHASIL] revenueChart mengembalikan tepat 6 label bulan', function () {
    $result = app(FinanceDashboardService::class)->getRevenueChartData();

    expect($result['labels'])->toHaveCount(6);
});

test('[BERHASIL] revenueChart mengembalikan 3 dataset (total, bulanan, harian)', function () {
    $result = app(FinanceDashboardService::class)->getRevenueChartData();

    expect($result['datasets'])->toHaveCount(3);
    expect($result['datasets'][0]['label'])->toBe('Total Pendapatan');
    expect($result['datasets'][1]['label'])->toBe('Sewa Bulanan');
    expect($result['datasets'][2]['label'])->toBe('Sewa Harian');
});

test('[BERHASIL] revenueChart setiap dataset memiliki 6 titik data', function () {
    $result = app(FinanceDashboardService::class)->getRevenueChartData();

    foreach ($result['datasets'] as $dataset) {
        expect($dataset['data'])->toHaveCount(6);
    }
});

test('[BERHASIL] revenueChart memuat nilai pendapatan invoice paid pada bulan yang benar', function () {
    // Buat invoice paid bulan ini
    Invoice::factory()->create([
        'status'     => InvoiceStatus::PAID,
        'amount'     => 1200000,
        'updated_at' => now(),
    ]);

    $result = app(FinanceDashboardService::class)->getRevenueChartData();

    // Bulan terakhir (index 5) adalah bulan ini
    expect($result['datasets'][0]['data'][5])->toBe(1200000.0);
});

// =========================================================
// 4.3 — getDueInvoicesWidget
// =========================================================

test('[BERHASIL] dueInvoices hanya mengembalikan invoice yang jatuh tempo dalam 7 hari ke depan', function () {
    // Masuk: due date besok
    Invoice::factory()->create(['status' => InvoiceStatus::UNPAID, 'due_date' => now()->addDay()]);
    // Masuk: due date sudah lewat (overdue juga masuk karena <= 7 hari dari skrg dalam konteks "due")
    Invoice::factory()->create(['status' => InvoiceStatus::UNPAID, 'due_date' => now()->subDay()]);
    // Tidak masuk: due date lebih dari 7 hari
    Invoice::factory()->create(['status' => InvoiceStatus::UNPAID, 'due_date' => now()->addDays(10)]);
    // Tidak masuk: sudah paid
    Invoice::factory()->create(['status' => InvoiceStatus::PAID, 'due_date' => now()->addDay()]);

    $result = app(FinanceDashboardService::class)->getDueInvoicesWidget();

    expect($result)->toHaveCount(2);
});

test('[BERHASIL] dueInvoices maksimal mengembalikan 5 invoice', function () {
    Invoice::factory()->count(8)->create([
        'status'   => InvoiceStatus::UNPAID,
        'due_date' => now()->addDays(2),
    ]);

    $result = app(FinanceDashboardService::class)->getDueInvoicesWidget();

    expect($result)->toHaveCount(5);
});

test('[BERHASIL] dueInvoices mengembalikan koleksi kosong jika tidak ada tagihan jatuh tempo', function () {
    $result = app(FinanceDashboardService::class)->getDueInvoicesWidget();

    expect($result)->toHaveCount(0);
});

// =========================================================
// 4.4 — getPendingPaymentsWidget
// =========================================================

test('[BERHASIL] pendingPayments hanya mengembalikan pembayaran dengan status pending', function () {
    Payment::factory()->count(3)->create(['status' => PaymentStatus::PENDING]);
    Payment::factory()->count(2)->create(['status' => PaymentStatus::VERIFIED]);

    $result = app(FinanceDashboardService::class)->getPendingPaymentsWidget();

    expect($result)->toHaveCount(3);
    $result->each(fn ($p) => expect($p->status)->toBe(PaymentStatus::PENDING));
});

test('[BERHASIL] pendingPayments maksimal mengembalikan 5 pembayaran', function () {
    Payment::factory()->count(8)->create(['status' => PaymentStatus::PENDING]);

    $result = app(FinanceDashboardService::class)->getPendingPaymentsWidget();

    expect($result)->toHaveCount(5);
});

test('[BERHASIL] pendingPayments mengembalikan koleksi kosong jika tidak ada yang pending', function () {
    Payment::factory()->count(3)->create(['status' => PaymentStatus::VERIFIED]);

    $result = app(FinanceDashboardService::class)->getPendingPaymentsWidget();

    expect($result)->toHaveCount(0);
});
