<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Finance\Models\FixedExpenseEntry;
use Modules\Setting\Models\AppSetting;
use Modules\Setting\Models\FeatureToggle;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function aktifkanFiturPengeluaranTetap(array $jenis = ['listrik', 'air', 'wifi']): void
{
    AppSetting::updateOrCreate(
        ['key' => 'feature_pengeluaran_tetap'],
        ['value' => 'true']
    );
    AppSetting::updateOrCreate(
        ['key' => 'pengeluaran_tetap_jenis_aktif'],
        ['value' => json_encode($jenis)]
    );
    $parent = FeatureToggle::firstOrCreate(
        ['key' => 'finance'],
        ['name' => 'Manajemen Keuangan', 'is_active' => true, 'is_locked' => false],
    );
    FeatureToggle::updateOrCreate(
        ['key' => 'finance_fixed_expense'],
        ['name' => 'Pengeluaran Tetap Bulanan', 'is_active' => true, 'is_locked' => false, 'parent_id' => $parent->id],
    );
}

function nonaktifkanFiturPengeluaranTetap(): void
{
    AppSetting::updateOrCreate(
        ['key' => 'feature_pengeluaran_tetap'],
        ['value' => 'false']
    );
    AppSetting::updateOrCreate(
        ['key' => 'pengeluaran_tetap_jenis_aktif'],
        ['value' => '[]']
    );
    $parent = FeatureToggle::firstOrCreate(
        ['key' => 'finance'],
        ['name' => 'Manajemen Keuangan', 'is_active' => true, 'is_locked' => false],
    );
    FeatureToggle::updateOrCreate(
        ['key' => 'finance_fixed_expense'],
        ['name' => 'Pengeluaran Tetap Bulanan', 'is_active' => false, 'is_locked' => false, 'parent_id' => $parent->id],
    );
}

beforeEach(function () {
    Cache::flush();
    $this->withoutMiddleware();
    nonaktifkanFiturPengeluaranTetap();
});

// =========================================================
// Fitur Guard — nonaktif
// =========================================================

test('[GAGAL] tidak bisa melihat list jika fitur nonaktif', function () {
    $response = $this->getJson('/api/finance/fixed-expenses');

    $response->assertForbidden();
});

test('[GAGAL] tidak bisa generate entri jika fitur nonaktif', function () {
    $response = $this->postJson('/api/finance/fixed-expenses/generate-bulan-ini');

    $response->assertForbidden();
});

test('[GAGAL] tidak bisa melihat status jika fitur nonaktif', function () {
    $response = $this->getJson('/api/finance/fixed-expenses/status');

    $response->assertForbidden();
});

// =========================================================
// Index
// =========================================================

test('[BERHASIL] admin dapat melihat daftar pengeluaran tetap', function () {
    aktifkanFiturPengeluaranTetap();

    FixedExpenseEntry::factory()->create(['jenis' => 'listrik', 'bulan' => 1, 'tahun' => 2026]);
    FixedExpenseEntry::factory()->create(['jenis' => 'listrik', 'bulan' => 2, 'tahun' => 2026]);
    FixedExpenseEntry::factory()->create(['jenis' => 'listrik', 'bulan' => 3, 'tahun' => 2026]);

    $response = $this->getJson('/api/finance/fixed-expenses');

    $response->assertOk()
        ->assertJsonFragment(['success' => true])
        ->assertJsonCount(3, 'data');
});

test('[BERHASIL] filter berdasarkan jenis bekerja dengan benar', function () {
    aktifkanFiturPengeluaranTetap();

    FixedExpenseEntry::factory()->create(['jenis' => 'listrik', 'bulan' => 1, 'tahun' => 2026]);
    FixedExpenseEntry::factory()->create(['jenis' => 'air',     'bulan' => 1, 'tahun' => 2026]);
    FixedExpenseEntry::factory()->create(['jenis' => 'wifi',    'bulan' => 1, 'tahun' => 2026]);

    $response = $this->getJson('/api/finance/fixed-expenses?jenis=listrik');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

// =========================================================
// Generate Bulan Ini
// =========================================================

test('[BERHASIL] generate membuat entri kosong untuk semua jenis aktif', function () {
    aktifkanFiturPengeluaranTetap(['listrik', 'air', 'wifi']);

    $bulan = (int) now()->format('n');
    $tahun = (int) now()->format('Y');

    $response = $this->postJson('/api/finance/fixed-expenses/generate-bulan-ini');

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonPath('data.jumlah_dibuat', 3);

    $this->assertDatabaseHas('fixed_expense_entries', ['jenis' => 'listrik', 'bulan' => $bulan, 'tahun' => $tahun, 'is_filled' => false]);
    $this->assertDatabaseHas('fixed_expense_entries', ['jenis' => 'air',     'bulan' => $bulan, 'tahun' => $tahun, 'is_filled' => false]);
    $this->assertDatabaseHas('fixed_expense_entries', ['jenis' => 'wifi',    'bulan' => $bulan, 'tahun' => $tahun, 'is_filled' => false]);
});

test('[BERHASIL] generate bersifat idempotent — tidak membuat duplikat', function () {
    aktifkanFiturPengeluaranTetap(['listrik', 'air']);

    $this->postJson('/api/finance/fixed-expenses/generate-bulan-ini');
    $response = $this->postJson('/api/finance/fixed-expenses/generate-bulan-ini');

    $response->assertOk()
        ->assertJsonPath('data.jumlah_dibuat', 0);

    $bulan = (int) now()->format('n');
    $tahun = (int) now()->format('Y');
    $this->assertEquals(2, FixedExpenseEntry::where('bulan', $bulan)->where('tahun', $tahun)->count());
});

test('[BERHASIL] generate mendukung query ?bulan dan ?tahun', function () {
    aktifkanFiturPengeluaranTetap(['listrik']);

    $response = $this->postJson('/api/finance/fixed-expenses/generate-bulan-ini?bulan=1&tahun=2025');

    $response->assertOk()
        ->assertJsonPath('data.jumlah_dibuat', 1);

    $this->assertDatabaseHas('fixed_expense_entries', ['jenis' => 'listrik', 'bulan' => 1, 'tahun' => 2025]);
});

// =========================================================
// Show
// =========================================================

test('[BERHASIL] admin dapat melihat detail entri pengeluaran tetap', function () {
    aktifkanFiturPengeluaranTetap();

    $entry = FixedExpenseEntry::factory()->create([
        'jenis'  => 'air',
        'bulan'  => 5,
        'tahun'  => 2026,
        'amount' => 250000,
    ]);

    $response = $this->getJson("/api/finance/fixed-expenses/{$entry->id}");

    $response->assertOk()
        ->assertJsonPath('data.jenis', 'air')
        ->assertJsonPath('data.amount', 250000);
});

test('[GAGAL] show mengembalikan 404 jika entri tidak ditemukan', function () {
    aktifkanFiturPengeluaranTetap();

    $response = $this->getJson('/api/finance/fixed-expenses/9999');

    $response->assertNotFound();
});

// =========================================================
// Update
// =========================================================

test('[BERHASIL] admin dapat mengisi nominal entri yang sudah di-generate', function () {
    aktifkanFiturPengeluaranTetap();

    $entry = FixedExpenseEntry::factory()->create([
        'jenis'     => 'listrik',
        'bulan'     => 6,
        'tahun'     => 2026,
        'amount'    => 0,
        'is_filled' => false,
    ]);

    $response = $this->putJson("/api/finance/fixed-expenses/{$entry->id}", [
        'amount' => 1350000,
        'notes'  => 'Tagihan PLN Juni',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.amount', 1350000)
        ->assertJsonPath('data.notes', 'Tagihan PLN Juni')
        ->assertJsonPath('data.is_filled', true);

    $this->assertDatabaseHas('fixed_expense_entries', [
        'id'        => $entry->id,
        'amount'    => '1350000.00',
        'is_filled' => true,
    ]);
});

test('[BERHASIL] update entri juga memperbarui record di tabel expenses', function () {
    aktifkanFiturPengeluaranTetap(['listrik']);

    $entry = FixedExpenseEntry::factory()->create([
        'jenis'  => 'listrik',
        'bulan'  => 6,
        'tahun'  => 2026,
        'amount' => 0,
        'is_filled' => false,
    ]);

    $this->putJson("/api/finance/fixed-expenses/{$entry->id}", ['amount' => 1500000]);

    $this->assertDatabaseHas('expenses', [
        'reference_id'   => $entry->id,
        'reference_type' => 'fixed_utility',
        'amount'         => '1500000.00',
    ]);
});

test('[GAGAL] update mengembalikan 404 jika entri tidak ditemukan', function () {
    aktifkanFiturPengeluaranTetap();

    $response = $this->putJson('/api/finance/fixed-expenses/9999', ['amount' => 100000]);

    $response->assertNotFound();
});

// =========================================================
// Status Endpoint
// =========================================================

test('[BERHASIL] status menunjukkan belum_diisi jika entri ada tapi is_filled = false', function () {
    aktifkanFiturPengeluaranTetap(['listrik', 'air', 'wifi']);

    $bulan = (int) now()->format('n');
    $tahun = (int) now()->format('Y');

    // Listrik sudah diisi, air & wifi belum
    FixedExpenseEntry::factory()->create(['jenis' => 'listrik', 'bulan' => $bulan, 'tahun' => $tahun, 'is_filled' => true,  'amount' => 1200000]);
    FixedExpenseEntry::factory()->create(['jenis' => 'air',     'bulan' => $bulan, 'tahun' => $tahun, 'is_filled' => false, 'amount' => 0]);

    $response = $this->getJson('/api/finance/fixed-expenses/status');

    $response->assertOk()
        ->assertJsonPath('data.semua_terisi', false)
        ->assertJsonPath('data.detail.listrik.filled', true)
        ->assertJsonPath('data.detail.air.filled', false)
        ->assertJsonPath('data.detail.wifi.filled', false);

    $belumDiisi = $response->json('data.belum_diisi');
    expect($belumDiisi)->toContain('air')
        ->toContain('wifi')
        ->not->toContain('listrik');
});

test('[BERHASIL] status semua_terisi = true jika semua jenis sudah diisi', function () {
    aktifkanFiturPengeluaranTetap(['listrik', 'air']);

    $bulan = (int) now()->format('n');
    $tahun = (int) now()->format('Y');

    FixedExpenseEntry::factory()->create(['jenis' => 'listrik', 'bulan' => $bulan, 'tahun' => $tahun, 'is_filled' => true, 'amount' => 1200000]);
    FixedExpenseEntry::factory()->create(['jenis' => 'air',     'bulan' => $bulan, 'tahun' => $tahun, 'is_filled' => true, 'amount' => 300000]);

    $response = $this->getJson('/api/finance/fixed-expenses/status');

    $response->assertOk()
        ->assertJsonPath('data.semua_terisi', true)
        ->assertJsonPath('data.belum_diisi', []);
});

test('[BERHASIL] status mendukung query ?bulan dan ?tahun untuk bulan lainnya', function () {
    aktifkanFiturPengeluaranTetap(['listrik']);

    FixedExpenseEntry::factory()->create(['jenis' => 'listrik', 'bulan' => 1, 'tahun' => 2026, 'is_filled' => true, 'amount' => 1000000]);

    $response = $this->getJson('/api/finance/fixed-expenses/status?bulan=1&tahun=2026');

    $response->assertOk()
        ->assertJsonPath('data.semua_terisi', true)
        ->assertJsonPath('data.bulan', 1)
        ->assertJsonPath('data.tahun', 2026);
});

test('[BERHASIL] status detail menyertakan entry_id meski belum diisi', function () {
    aktifkanFiturPengeluaranTetap(['listrik']);

    $bulan = (int) now()->format('n');
    $tahun = (int) now()->format('Y');

    $entry = FixedExpenseEntry::factory()->create([
        'jenis'     => 'listrik',
        'bulan'     => $bulan,
        'tahun'     => $tahun,
        'is_filled' => false,
        'amount'    => 0,
    ]);

    $response = $this->getJson('/api/finance/fixed-expenses/status');

    $response->assertOk()
        ->assertJsonPath('data.detail.listrik.filled', false)
        ->assertJsonPath('data.detail.listrik.entry_id', $entry->id);
});
