<?php

namespace Modules\Setting\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Services\SettingService;

class SettingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $settingService = app(SettingService::class);

        $settings = [
            'wisma_name' => 'Wisma Amal Gorontalo',
            'wisma_address' => 'Jl. Wisma Amal No. 1, Gorontalo',
            'wisma_phone' => '0811-4300-XXX',
            'wisma_email' => 'wismaamal@email.com',
            'wisma_maps_link' => 'https://maps.google.com',
            'wisma_operational_hours' => 'Senin - Sabtu, 08.00 - 17.00 WITA',
            'bank_name' => 'Bank BSI',
            'bank_account' => '7123456789',
            'bank_holder' => 'Wisma Amal Gorontalo',
            'landing_header_title' => 'Temukan Kenyamanan Tinggal di Wisma Amal Gorontalo',
            'landing_header_subtitle' => 'Mengecek ketersediaan kamar, melihat fasilitas, dan melakukan reservasi secara cepat.',
            'landing_facilities' => 'WiFi Cepat, Keamanan 24 Jam, Parkir Luas, Dapur Bersama',
            'landing_highlighted_rooms' => '[]',
        ];

        foreach ($settings as $key => $value) {
            $settingService->updateSetting($key, $value);
        }

        $settingService->setEnabledMidtransPaymentMethods(['qris', 'gopay', 'bca_va', 'mandiri_va']);
        $settingService->setMidtransFeeConfig(SettingService::defaultMidtransFeeConfig());

        // Pengeluaran Tetap (default: nonaktif)
        $settingService->setJenisPengeluaranTetapAktif([]);
    }
}
