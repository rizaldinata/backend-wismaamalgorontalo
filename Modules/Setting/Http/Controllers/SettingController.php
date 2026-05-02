<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Setting\Services\SettingService;

class SettingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SettingService $settingService
    ) {}

    /**
     * Display a listing of the resource for Admin Panel (Frontend UI).
     */
    public function index(): JsonResponse
    {
        $settings = $this->settingService->getPublicSettings();

        return $this->apiSuccess($settings, 'Konfigurasi aplikasi berhasil dimuat');
    }

    /**
     * Update configuration parameters dynamically.
     */
    public function updateBulk(Request $request): JsonResponse
    {
        $validator = $request->validate([
            'settings'                              => 'required|array',
            'settings.wisma_name'                   => 'nullable|string|max:100',
            'settings.feature_daily_rental'         => 'nullable|boolean',
            'settings.feature_whatsapp_receipt'     => 'nullable|boolean',
            'settings.feature_whatsapp_pdf_link'    => 'nullable|boolean',
        ]);

        $settingsToSave = $validator['settings'];

        foreach ($settingsToSave as $key => $value) {
            $this->settingService->updateSetting($key, $value);
        }

        return $this->apiSuccess($this->settingService->getPublicSettings(), 'Seluruh konfigurasi internal berhasil diperbarui!');
    }
}
