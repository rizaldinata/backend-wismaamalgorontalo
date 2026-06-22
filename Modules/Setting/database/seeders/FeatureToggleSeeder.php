<?php

namespace Modules\Setting\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Models\FeatureToggle;

class FeatureToggleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['key' => 'room',         'name' => 'Kamar',        'is_locked' => true,  'sort_order' => 1],
            ['key' => 'schedule',     'name' => 'Jadwal',        'is_locked' => true,  'sort_order' => 2],
            ['key' => 'finance',      'name' => 'Keuangan',      'is_locked' => false, 'sort_order' => 3],
            ['key' => 'maintenance',  'name' => 'Pemeliharaan',   'is_locked' => false, 'sort_order' => 4],
            ['key' => 'guest',        'name' => 'Tamu',           'is_locked' => false, 'sort_order' => 5],
            ['key' => 'inventory',    'name' => 'Inventaris',     'is_locked' => false, 'sort_order' => 6],
            ['key' => 'notification', 'name' => 'Notifikasi',     'is_locked' => false, 'sort_order' => 7],
        ];

        foreach ($modules as $moduleData) {
            FeatureToggle::firstOrCreate(
                ['key' => $moduleData['key']],
                [
                    'name' => $moduleData['name'],
                    'is_locked' => $moduleData['is_locked'],
                    'sort_order' => $moduleData['sort_order'],
                    'is_active' => true,
                    'parent_id' => null,
                ]
            );
        }

        $finance = FeatureToggle::where('key', 'finance')->first();
        $notification = FeatureToggle::where('key', 'notification')->first();

        $features = [
            // Finance children
            ['parent_id' => $finance->id, 'key' => 'feature_payment_midtrans',  'name' => 'Pembayaran Midtrans', 'sort_order' => 1, 'is_active' => true],
            ['parent_id' => $finance->id, 'key' => 'feature_daily_rental',      'name' => 'Sewa Harian', 'sort_order' => 2, 'is_active' => true],
            ['parent_id' => $finance->id, 'key' => 'feature_pengeluaran_tetap', 'name' => 'Pengeluaran Tetap', 'sort_order' => 3, 'is_active' => false], // Default false as before
            // Notification children
            ['parent_id' => $notification->id, 'key' => 'feature_whatsapp_receipt',  'name' => 'Notifikasi WhatsApp', 'sort_order' => 1, 'is_active' => true],
            ['parent_id' => $notification->id, 'key' => 'feature_whatsapp_pdf_link', 'name' => 'Link PDF di WhatsApp', 'sort_order' => 2, 'is_active' => true],
        ];

        foreach ($features as $featureData) {
            FeatureToggle::firstOrCreate(
                ['key' => $featureData['key']],
                [
                    'name' => $featureData['name'],
                    'is_locked' => false,
                    'sort_order' => $featureData['sort_order'],
                    'is_active' => $featureData['is_active'],
                    'parent_id' => $featureData['parent_id'],
                ]
            );
        }
    }
}
