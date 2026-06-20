<?php

use App\Contracts\ConfigProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Models\FixedExpenseEntry;
use Modules\Finance\Repositories\Contracts\FixedExpenseEntryRepositoryInterface;
use Modules\Finance\Services\ExpenseService;
use Modules\Finance\Services\FixedExpenseService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function buatServiceDenganFitur(bool $aktif, array $jenisAktif = ['listrik', 'air', 'wifi']): FixedExpenseService
{
    $mockRepo    = \Mockery::mock(FixedExpenseEntryRepositoryInterface::class);
    $mockExpense = \Mockery::mock(ExpenseService::class);
    $mockSetting = \Mockery::mock(ConfigProviderInterface::class);

    $mockSetting->shouldReceive('isPengeluaranTetapEnabled')->andReturn($aktif);
    $mockSetting->shouldReceive('getJenisPengeluaranTetapAktif')->andReturn($jenisAktif);

    return new FixedExpenseService($mockRepo, $mockExpense, $mockSetting);
}

test('[GAGAL] guardFeatureAktif melempar DomainException jika fitur nonaktif', function () {
    $service = buatServiceDenganFitur(false);

    expect(fn () => $service->guardFeatureAktif())
        ->toThrow(DomainException::class, 'Fitur Pengeluaran Tetap tidak diaktifkan');
});

test('[BERHASIL] guardFeatureAktif tidak melempar exception jika fitur aktif', function () {
    $service = buatServiceDenganFitur(true);

    expect(fn () => $service->guardFeatureAktif())->not->toThrow(DomainException::class);
});

test('[BERHASIL] generateBulanIni memanggil repository dengan jenis aktif dan periode', function () {
    $bulan = (int) now()->format('n');
    $tahun = (int) now()->format('Y');

    $mockRepo    = \Mockery::mock(FixedExpenseEntryRepositoryInterface::class);
    $mockExpense = \Mockery::mock(ExpenseService::class);
    $mockSetting = \Mockery::mock(ConfigProviderInterface::class);

    $mockSetting->shouldReceive('isPengeluaranTetapEnabled')->andReturn(true);
    $mockSetting->shouldReceive('getJenisPengeluaranTetapAktif')->andReturn(['listrik', 'air']);
    $mockRepo->shouldReceive('generateBulanan')->with(['listrik', 'air'], $bulan, $tahun)->once()->andReturn(2);

    $service = new FixedExpenseService($mockRepo, $mockExpense, $mockSetting);

    expect($service->generateBulanIni())->toBe(2);
});

test('[GAGAL] generateBulanIni melempar DomainException jika fitur nonaktif', function () {
    $service = buatServiceDenganFitur(false);

    expect(fn () => $service->generateBulanIni())
        ->toThrow(DomainException::class, 'Fitur Pengeluaran Tetap tidak diaktifkan');
});
