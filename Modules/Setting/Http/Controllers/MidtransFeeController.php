<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Setting\Http\Requests\UpdateMidtransFeeRequest;
use Modules\Setting\Services\SettingService;

class MidtransFeeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SettingService $settingService
    ) {}

    public function index(): JsonResponse
    {
        $config  = $this->settingService->getMidtransFeeConfig();
        $catalog = SettingService::midtransFeeCatalog();

        $fees = [];
        foreach ($catalog as $key => $meta) {
            $fees[] = array_merge(['key' => $key], $meta, $config['fees'][$key] ?? []);
        }

        return $this->apiSuccess([
            'bearer' => $config['bearer'],
            'fees'   => $fees,
        ], 'Konfigurasi biaya transaksi Midtrans berhasil dimuat');
    }

    public function update(UpdateMidtransFeeRequest $request): JsonResponse
    {
        $this->settingService->setMidtransFeeConfig($request->validated());

        $config  = $this->settingService->getMidtransFeeConfig();
        $catalog = SettingService::midtransFeeCatalog();

        $fees = [];
        foreach ($catalog as $key => $meta) {
            $fees[] = array_merge(['key' => $key], $meta, $config['fees'][$key] ?? []);
        }

        return $this->apiSuccess([
            'bearer' => $config['bearer'],
            'fees'   => $fees,
        ], 'Konfigurasi biaya transaksi Midtrans berhasil diperbarui');
    }
}
