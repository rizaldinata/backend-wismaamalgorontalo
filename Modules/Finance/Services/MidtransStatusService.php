<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransStatusService
{
    private const CACHE_KEY = 'midtrans_payment_methods_status';

    private const CACHE_TTL = 300; // 5 menit

    /**
     * Cek status maintenance setiap metode dari daftar kode yang diberikan.
     * Fail-open: jika API Midtrans tidak merespons, semua metode dianggap tersedia.
     *
     * @param  string[]  $codes
     * @return array<array{code: string, maintenance: bool, available: bool}>
     */
    public function getMethodsStatus(array $codes): array
    {
        $maintenanceMap = $this->fetchWithCache();

        return array_map(fn (string $code) => [
            'code' => $code,
            'maintenance' => $maintenanceMap[$code] ?? false,
            'available' => ! ($maintenanceMap[$code] ?? false),
        ], $codes);
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function fetchWithCache(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->fetchFromMidtrans());
    }

    private function fetchFromMidtrans(): array
    {
        try {
            $serverKey = config('finance.midtrans.server_key', '');
            $isProduction = config('finance.midtrans.is_production', false);
            $baseUrl = $isProduction
                ? 'https://api.midtrans.com'
                : 'https://api.sandbox.midtrans.com';

            $response = Http::withBasicAuth($serverKey, '')
                ->timeout(5)
                ->acceptJson()
                ->get("{$baseUrl}/v1/payment-methods");

            if ($response->successful()) {
                return $this->parseResponse($response->json());
            }
        } catch (\Throwable $e) {
            Log::warning('Midtrans status check failed, failing open.', ['error' => $e->getMessage()]);
        }

        return []; // fail open — kosong = semua metode tersedia
    }

    private function parseResponse(?array $data): array
    {
        if (empty($data) || ! isset($data['payment_methods'])) {
            return [];
        }

        $maintenance = [];
        foreach ($data['payment_methods'] as $method) {
            $code = strtolower($method['code'] ?? '');
            $status = strtolower($method['status'] ?? '');
            if ($code !== '' && $status === 'maintenance') {
                $maintenance[$code] = true;
            }
        }

        return $maintenance;
    }
}
