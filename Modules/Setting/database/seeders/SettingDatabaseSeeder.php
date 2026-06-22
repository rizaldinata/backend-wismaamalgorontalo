<?php

namespace Modules\Setting\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Services\SettingService;

class SettingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $settingService = app(SettingService::class);
        $this->call([
            FeatureToggleSeeder::class,
        ]);

        $settings = [
            'wisma_name' => 'Wisma Amal Gorontalo',
            'bank_name' => 'Bank BSI',
            'bank_account' => '7123456789',
            'bank_holder' => 'Wisma Amal Gorontalo',
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
