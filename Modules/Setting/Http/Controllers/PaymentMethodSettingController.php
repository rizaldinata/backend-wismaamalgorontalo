<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Setting\Http\Requests\UpdatePaymentMethodRequest;
use Modules\Setting\Services\SettingService;

class PaymentMethodSettingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SettingService $settingService
    ) {}

    public function index(): JsonResponse
    {
        $catalog = SettingService::midtransMethodCatalog();
        $enabled = $this->settingService->getEnabledMidtransPaymentMethods();

        $data = array_map(fn (string $code, string $label) => [
            'code'    => $code,
            'label'   => $label,
            'enabled' => in_array($code, $enabled, true),
        ], array_keys($catalog), $catalog);

        return $this->apiSuccess(array_values($data), 'Daftar metode pembayaran Midtrans berhasil dimuat');
    }

    public function update(UpdatePaymentMethodRequest $request): JsonResponse
    {
        $methods = array_unique($request->validated()['enabled_methods']);
        $this->settingService->setEnabledMidtransPaymentMethods(array_values($methods));

        $catalog  = SettingService::midtransMethodCatalog();
        $enabled  = $this->settingService->getEnabledMidtransPaymentMethods();

        $data = array_map(fn (string $code, string $label) => [
            'code'    => $code,
            'label'   => $label,
            'enabled' => in_array($code, $enabled, true),
        ], array_keys($catalog), $catalog);

        return $this->apiSuccess(array_values($data), 'Metode pembayaran Midtrans berhasil diperbarui');
    }
}
