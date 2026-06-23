<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $fiturPengeluaranTetapAktif = (bool) $request->input('settings.feature_pengeluaran_tetap', false);

        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.wisma_name' => 'nullable|string|max:100',
            'settings.wisma_address' => 'nullable|string',
            'settings.wisma_phone' => 'nullable|string|max:20',
            'settings.wisma_email' => 'nullable|string|max:100',
            'settings.wisma_maps_link' => 'nullable|string',
            'settings.wisma_operational_hours' => 'nullable|string',
            'settings.feature_daily_rental' => 'nullable|boolean',
            'settings.feature_whatsapp_receipt' => 'nullable|boolean',
            'settings.feature_whatsapp_pdf_link' => 'nullable|boolean',
            'settings.feature_payment_midtrans' => 'nullable|boolean',
            'settings.bank_name' => 'nullable|string|max:100',
            'settings.bank_account' => 'nullable|string|max:50',
            'settings.bank_holder' => 'nullable|string|max:100',
            'settings.landing_header_title' => 'nullable|string',
            'settings.landing_header_subtitle' => 'nullable|string',
            'settings.landing_facilities' => 'nullable|string',
            'settings.landing_highlighted_rooms' => 'nullable|array',
            'settings.feature_pengeluaran_tetap' => 'nullable|boolean',
            'settings.pengeluaran_tetap_jenis_aktif' => [
                'nullable',
                'array',
            ],
            'settings.pengeluaran_tetap_jenis_aktif.*' => 'in:listrik,air,wifi',
        ]);

        if ($fiturPengeluaranTetapAktif) {
            $jenis = $validated['settings']['pengeluaran_tetap_jenis_aktif'] ?? [];
            if (empty($jenis)) {
                // Auto-disable feature if no types selected to avoid errors on unrelated updates
                $validated['settings']['feature_pengeluaran_tetap'] = false;
                $request->merge(['settings' => array_merge($request->input('settings', []), ['feature_pengeluaran_tetap' => false])]);
            }
        }

        $settingsToSave = $validated['settings'];

        // Pengeluaran tetap disimpan via method khusus (JSON array)
        if (array_key_exists('pengeluaran_tetap_jenis_aktif', $settingsToSave)) {
            $this->settingService->setJenisPengeluaranTetapAktif(
                $settingsToSave['pengeluaran_tetap_jenis_aktif'] ?? []
            );
            unset($settingsToSave['pengeluaran_tetap_jenis_aktif']);
        }

        foreach ($settingsToSave as $key => $value) {
            $this->settingService->updateSetting($key, $value);
        }

        return $this->apiSuccess($this->settingService->getPublicSettings(), 'Seluruh konfigurasi internal berhasil diperbarui!');
    }
}
