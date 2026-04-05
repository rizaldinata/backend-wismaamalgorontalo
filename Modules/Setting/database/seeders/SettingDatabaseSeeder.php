<?php

namespace Modules\Setting\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Models\AppSetting;

class SettingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        AppSetting::updateOrCreate(
            ['key' => 'feature-midtrans-payment'],
            [
                'value' => 'false',
                'description' => 'Aktifkan metode pembayaran otomatis via Midtrans'
            ]
        );

        AppSetting::updateOrCreate(
            ['key' => 'feature-daily-rental'],
            [
                'value' => 'false',
                'description' => 'Aktifkan opsi penyewaan kamar harian (Hotel)'
            ]
        );
    }
}
