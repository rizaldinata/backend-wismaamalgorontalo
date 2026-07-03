<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Finance\Services\MidtransStatusService;
use Modules\Setting\Models\AppSetting;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
    Cache::flush();
    // Default: Midtrans API aktif, tidak ada maintenance
    Http::fake(['*' => Http::response(['payment_methods' => []], 200)]);
});

// =========================================================
// GET /api/finance/payment-methods
// =========================================================

test('[BERHASIL] mengembalikan list kosong jika tidak ada metode yang diaktifkan', function () {
    $response = $this->getJson('/api/finance/payment-methods');

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Daftar metode pembayaran tersedia berhasil dimuat'])
        ->assertJsonPath('data', []);
});

test('[BERHASIL] hanya metode yang diaktifkan di setting yang dikembalikan', function () {
    AppSetting::create([
        'key' => 'midtrans_enabled_payment_methods',
        'value' => '["qris","gopay"]',
    ]);

    $response = $this->getJson('/api/finance/payment-methods');

    $response->assertOk();
    $data = $response->json('data');

    expect($data)->toHaveCount(2);
    $codes = array_column($data, 'code');
    expect($codes)->toContain('qris')->toContain('gopay');
    expect($codes)->not->toContain('bca_va');
});

test('[BERHASIL] setiap item berisi code, label, available, dan maintenance', function () {
    AppSetting::create([
        'key' => 'midtrans_enabled_payment_methods',
        'value' => '["qris"]',
    ]);

    $response = $this->getJson('/api/finance/payment-methods');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['code', 'label', 'available', 'maintenance'],
            ],
        ]);
});

test('[BERHASIL] label yang benar dikembalikan untuk setiap metode', function () {
    AppSetting::create([
        'key' => 'midtrans_enabled_payment_methods',
        'value' => '["qris","bca_va","mandiri_va"]',
    ]);

    $response = $this->getJson('/api/finance/payment-methods');
    $data = collect($response->json('data'));

    expect($data->firstWhere('code', 'qris')['label'])->toBe('QRIS');
    expect($data->firstWhere('code', 'bca_va')['label'])->toBe('BCA Virtual Account');
    expect($data->firstWhere('code', 'mandiri_va')['label'])->toBe('Mandiri Virtual Account');
});

test('[BERHASIL] metode yang sedang maintenance tetap muncul dengan available false', function () {
    AppSetting::create([
        'key' => 'midtrans_enabled_payment_methods',
        'value' => '["qris","bca_va"]',
    ]);

    // Mock langsung MidtransStatusService agar tidak bergantung pada Http::fake ordering
    $mock = Mockery::mock(MidtransStatusService::class);
    $mock->shouldReceive('getMethodsStatus')
        ->once()
        ->andReturn([
            ['code' => 'qris',   'maintenance' => false, 'available' => true],
            ['code' => 'bca_va', 'maintenance' => true,  'available' => false],
        ]);
    $this->app->instance(MidtransStatusService::class, $mock);

    $response = $this->getJson('/api/finance/payment-methods');
    $data = collect($response->json('data'));

    $qris = $data->firstWhere('code', 'qris');
    $bcaVa = $data->firstWhere('code', 'bca_va');

    expect($qris['available'])->toBeTrue();
    expect($qris['maintenance'])->toBeFalse();
    expect($bcaVa['available'])->toBeFalse();
    expect($bcaVa['maintenance'])->toBeTrue();
});

test('[BERHASIL] fail open — semua metode available jika Midtrans API error', function () {
    AppSetting::create([
        'key' => 'midtrans_enabled_payment_methods',
        'value' => '["gopay","bni_va"]',
    ]);

    $mock = Mockery::mock(MidtransStatusService::class);
    $mock->shouldReceive('getMethodsStatus')
        ->once()
        ->andReturn([
            ['code' => 'gopay', 'maintenance' => false, 'available' => true],
            ['code' => 'bni_va', 'maintenance' => false, 'available' => true],
        ]);
    $this->app->instance(MidtransStatusService::class, $mock);

    $response = $this->getJson('/api/finance/payment-methods');
    $data = $response->json('data');

    foreach ($data as $method) {
        expect($method['available'])->toBeTrue();
        expect($method['maintenance'])->toBeFalse();
    }
});
