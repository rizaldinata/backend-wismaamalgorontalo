<?php

namespace Modules\Finance\Http\Controllers;

use App\Contracts\ConfigProviderInterface;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Finance\Services\MidtransStatusService;
use Modules\Setting\Services\SettingService;

class PaymentMethodController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ConfigProviderInterface $settingService,
        private readonly MidtransStatusService $statusService,
    ) {}

    public function index(): JsonResponse
    {
        $catalog  = SettingService::midtransMethodCatalog();
        $enabled  = $this->settingService->getEnabledMidtransPaymentMethods();

        // Hanya metode yang aktif di setting yang diproses
        $activeCodes = array_filter(array_keys($catalog), fn ($code) => in_array($code, $enabled, true));

        if (empty($activeCodes)) {
            return $this->apiSuccess([], 'Daftar metode pembayaran tersedia berhasil dimuat');
        }

        $statusList = $this->statusService->getMethodsStatus(array_values($activeCodes));

        $data = array_map(fn (array $status) => [
            'code'        => $status['code'],
            'label'       => $catalog[$status['code']] ?? $status['code'],
            'available'   => $status['available'],
            'maintenance' => $status['maintenance'],
        ], $statusList);

        return $this->apiSuccess(array_values($data), 'Daftar metode pembayaran tersedia berhasil dimuat');
    }
}
