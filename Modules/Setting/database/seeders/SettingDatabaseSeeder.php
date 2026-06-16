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
            'feature_daily_rental' => 'true',

            'feature_whatsapp_receipt' => 'true',
            'feature_whatsapp_pdf_link' => 'true',
            'feature_payment_midtrans' => 'true',

            'bank_name' => 'Bank BSI',
            'bank_account' => '7123456789',
            'bank_holder' => 'Wisma Amal Gorontalo',
        ];

        foreach ($settings as $key => $value) {
            $settingService->updateSetting($key, $value);
        }
    }
}
