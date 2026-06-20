<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Finance\Services\MidtransStatusService;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Cache::flush();
});

// =========================================================
// getMethodsStatus — normal flow
// =========================================================

test('[BERHASIL] mengembalikan available true untuk semua metode jika API merespons sukses tanpa maintenance', function () {
    Http::fake([
        '*' => Http::response([
            'payment_methods' => [
                ['code' => 'QRIS', 'status' => 'active'],
                ['code' => 'GOPAY', 'status' => 'active'],
            ],
        ], 200),
    ]);

    $service = new MidtransStatusService;
    $result  = $service->getMethodsStatus(['qris', 'gopay']);

    expect($result)->toHaveCount(2);
    expect($result[0])->toMatchArray(['code' => 'qris', 'maintenance' => false, 'available' => true]);
    expect($result[1])->toMatchArray(['code' => 'gopay', 'maintenance' => false, 'available' => true]);
});

test('[BERHASIL] metode dengan status maintenance ditandai maintenance true dan available false', function () {
    Http::fake([
        '*' => Http::response([
            'payment_methods' => [
                ['code' => 'QRIS', 'status' => 'active'],
                ['code' => 'BCA_VA', 'status' => 'maintenance'],
            ],
        ], 200),
    ]);

    $service = new MidtransStatusService;
    $result  = collect($service->getMethodsStatus(['qris', 'bca_va']));

    $bca = $result->firstWhere('code', 'bca_va');
    expect($bca['maintenance'])->toBeTrue();
    expect($bca['available'])->toBeFalse();

    $qris = $result->firstWhere('code', 'qris');
    expect($qris['maintenance'])->toBeFalse();
    expect($qris['available'])->toBeTrue();
});

// =========================================================
// fail-open — API error
// =========================================================

test('[BERHASIL] fail open dan semua metode tersedia ketika API Midtrans mengembalikan 500', function () {
    Http::fake(['*' => Http::response(null, 500)]);

    $service = new MidtransStatusService;
    $result  = $service->getMethodsStatus(['qris', 'gopay', 'bca_va']);

    foreach ($result as $item) {
        expect($item['maintenance'])->toBeFalse();
        expect($item['available'])->toBeTrue();
    }
});

test('[BERHASIL] fail open ketika koneksi ke Midtrans timeout', function () {
    Http::fake(['*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout')]);

    $service = new MidtransStatusService;
    $result  = $service->getMethodsStatus(['qris']);

    expect($result[0]['available'])->toBeTrue();
    expect($result[0]['maintenance'])->toBeFalse();
});

test('[BERHASIL] fail open ketika response tidak memiliki key payment_methods', function () {
    Http::fake(['*' => Http::response(['status' => 'ok'], 200)]);

    $service = new MidtransStatusService;
    $result  = $service->getMethodsStatus(['gopay']);

    expect($result[0]['available'])->toBeTrue();
});

// =========================================================
// cache
// =========================================================

test('[BERHASIL] result di-cache sehingga API hanya dipanggil sekali', function () {
    Http::fake(['*' => Http::response(['payment_methods' => []], 200)]);

    $service = new MidtransStatusService;
    $service->getMethodsStatus(['qris']);
    $service->getMethodsStatus(['gopay']);
    $service->getMethodsStatus(['bca_va']);

    Http::assertSentCount(1);
});

test('[BERHASIL] forgetCache memaksa fetch ulang ke API', function () {
    Http::fake(['*' => Http::response(['payment_methods' => []], 200)]);

    $service = new MidtransStatusService;
    $service->getMethodsStatus(['qris']);
    $service->forgetCache();
    $service->getMethodsStatus(['qris']);

    Http::assertSentCount(2);
});
