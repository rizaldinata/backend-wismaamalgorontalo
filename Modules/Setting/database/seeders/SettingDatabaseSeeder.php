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
            'feature_midtrans_payment' => 'true',
            'feature_daily_rental' => 'true',

            'feature_whatsapp_receipt' => 'true',
            'feature_whatsapp_pdf_link' => 'true',

            'midtrans_enabled_payments' => json_encode(['gopay', 'shopeepay', 'qris']),
            'midtrans_server_key' => 'SB-Mid-server-XXXXX',
            'midtrans_client_key' => 'SB-Mid-client-XXXXX',
        ];

        foreach ($settings as $key => $value) {
            $settingService->updateSetting($key, $value);
        }
    }
}
