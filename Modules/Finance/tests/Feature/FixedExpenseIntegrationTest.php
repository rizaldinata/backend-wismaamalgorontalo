<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Models\FixedExpenseEntry;
use Modules\Finance\Services\FinanceDashboardService;
use Modules\Finance\Services\FixedExpenseService;
use Modules\Setting\Models\AppSetting;
use Modules\Setting\Services\SettingService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function setupFiturAktif(array $jenis = ['listrik', 'wifi']): void
{
    AppSetting::updateOrCreate(['key' => 'feature_pengeluaran_tetap'], ['value' => 'true']);
    AppSetting::updateOrCreate(['key' => 'pengeluaran_tetap_jenis_aktif'], ['value' => json_encode($jenis)]);
}

function setupFiturNonaktif(): void
{
    AppSetting::updateOrCreate(['key' => 'feature_pengeluaran_tetap'], ['value' => 'false']);
    AppSetting::updateOrCreate(['key' => 'pengeluaran_tetap_jenis_aktif'], ['value' => '[]']);
}

// Dashboard KPI
test('[INTEGRASI] KPI summary menyertakan pengeluaran_tetap_status saat fitur aktif', function () {
    setupFiturAktif(['listrik', 'wifi']);
    FixedExpenseEntry::factory()->create(['jenis' => 'listrik', 'bulan' => 6, 'tahun' => 2026, 'amount' => 1200000, 'is_filled' => true]);

    $kpi = app(FinanceDashboardService::class)->getKpiSummary(6, 2026);

    expect($kpi)->toHaveKey('pengeluaran_tetap_status');
    expect($kpi['pengeluaran_tetap_status']['semua_terisi'])->toBeFalse();
    expect($kpi['pengeluaran_tetap_status']['belum_diisi'])->toContain('wifi');
    expect($kpi['pengeluaran_tetap_status']['detail']['listrik']['filled'])->toBeTrue();
    expect($kpi['pengeluaran_tetap_status']['detail']['wifi']['filled'])->toBeFalse();
});

test('[INTEGRASI] KPI summary TIDAK menyertakan pengeluaran_tetap_status saat fitur nonaktif', function () {
    setupFiturNonaktif();

    $kpi = app(FinanceDashboardService::class)->getKpiSummary(6, 2026);

    expect($kpi)->not->toHaveKey('pengeluaran_tetap_status');
});

test('[INTEGRASI] KPI semua_terisi = true jika semua jenis terisi', function () {
    setupFiturAktif(['listrik', 'wifi']);
    $bulan = (int) now()->format('n');
    $tahun = (int) now()->format('Y');
    FixedExpenseEntry::factory()->create(['jenis' => 'listrik', 'bulan' => $bulan, 'tahun' => $tahun]);
    FixedExpenseEntry::factory()->create(['jenis' => 'wifi',    'bulan' => $bulan, 'tahun' => $tahun]);

    $kpi = app(FinanceDashboardService::class)->getKpiSummary();

    expect($kpi['pengeluaran_tetap_status']['semua_terisi'])->toBeTrue();
    expect($kpi['pengeluaran_tetap_status']['belum_diisi'])->toBeEmpty();
});

// Public Settings
test('[INTEGRASI] getPublicSettings menyertakan key fitur pengeluaran tetap', function () {
    setupFiturAktif(['listrik', 'air']);

    $public = app(SettingService::class)->getPublicSettings();

    expect($public)->toHaveKey('feature_pengeluaran_tetap');
    expect($public)->toHaveKey('pengeluaran_tetap_jenis_aktif');
    expect($public['feature_pengeluaran_tetap'])->toBeTrue();
    expect($public['pengeluaran_tetap_jenis_aktif'])->toBe(['listrik', 'air']);
});

// Update Settings validasi
test('[INTEGRASI] update-bulk ditolak jika fitur aktif tapi jenis kosong', function () {
    $this->withoutMiddleware();
    $response = $this->postJson('/api/v1/settings/update-bulk', [
        'settings' => [
            'feature_pengeluaran_tetap'     => true,
            'pengeluaran_tetap_jenis_aktif' => [],
        ],
    ]);

    $response->assertUnprocessable();
});

test('[INTEGRASI] update-bulk berhasil jika fitur aktif dengan minimal satu jenis', function () {
    $this->withoutMiddleware();
    $response = $this->postJson('/api/v1/settings/update-bulk', [
        'settings' => [
            'feature_pengeluaran_tetap'     => true,
            'pengeluaran_tetap_jenis_aktif' => ['listrik'],
        ],
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('app_settings', ['key' => 'feature_pengeluaran_tetap', 'value' => 'true']);
    $this->assertDatabaseHas('app_settings', ['key' => 'pengeluaran_tetap_jenis_aktif', 'value' => '["listrik"]']);
});

test('[INTEGRASI] update-bulk berhasil nonaktifkan tanpa pilih jenis', function () {
    $this->withoutMiddleware();
    $response = $this->postJson('/api/v1/settings/update-bulk', [
        'settings' => [
            'feature_pengeluaran_tetap'     => false,
            'pengeluaran_tetap_jenis_aktif' => [],
        ],
    ]);

    $response->assertOk();
});
