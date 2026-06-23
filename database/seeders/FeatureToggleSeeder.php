<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Models\FeatureToggle;
use Illuminate\Support\Facades\DB;

class FeatureToggleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        FeatureToggle::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. AUTH (Core)
        $authModule = FeatureToggle::create([
            'name' => 'Login Akun',
            'key' => 'auth',
            'description' => 'Autentikasi, Hak Akses, & Role (Wajib)',
            'icon' => 'lock',
            'is_active' => true,
            'is_locked' => true,
        ]);

        $authFeatures = [
            ['name' => 'Registrasi Akun', 'key' => 'auth_registration', 'description' => 'Mengizinkan pendaftaran calon penghuni langsung lewat aplikasi.', 'icon' => 'app_registration'],
        ];

        foreach ($authFeatures as $feat) {
            FeatureToggle::create([
                'parent_id' => $authModule->id,
                'name' => $feat['name'],
                'key' => $feat['key'],
                'description' => $feat['description'],
                'icon' => $feat['icon'],
                'is_active' => true,
                'is_locked' => false,
            ]);
        }

        // 1. CORE MODULES (Locked)
        $coreModules = [
            ['name' => 'Room', 'key' => 'room', 'description' => 'Manajemen Kamar & Fasilitas (Wajib)', 'icon' => 'meeting_room'],
            ['name' => 'Schedule', 'key' => 'schedule', 'description' => 'Pencatatan Penghuni & Reservasi (Wajib)', 'icon' => 'calendar_month'],
            ['name' => 'Setting', 'key' => 'setting', 'description' => 'Pengaturan Utama Sistem (Wajib)', 'icon' => 'settings'],
        ];

        foreach ($coreModules as $mod) {
            FeatureToggle::create([
                'name' => $mod['name'],
                'key' => $mod['key'],
                'description' => $mod['description'],
                'icon' => $mod['icon'],
                'is_active' => true,
                'is_locked' => true, // Locked for core modules
            ]);
        }

        // 2. MAINTENANCE & INVENTORY (Togglable)
        // Group them under a virtual parent or physical module. We'll use 'FacilityManagement' as parent.
        $facilityModule = FeatureToggle::create([
            'name' => 'Fasilitas & Pemeliharaan',
            'key' => 'facility_management', // virtual parent or we map it to Maintenance + Inventory in backend
            'description' => 'Manajemen aset kos, jadwal pembersihan, dan pelaporan kerusakan.',
            'icon' => 'home_repair_service',
            'is_active' => true,
            'is_locked' => false,
        ]);

        $facilityFeatures = [
            ['name' => 'Manajemen Inventaris', 'key' => 'inventory', 'description' => 'Pendataan barang/aset di kos dan di dalam kamar.', 'icon' => 'inventory_2'],
            ['name' => 'Jadwal Maintenance', 'key' => 'maintenance_schedule', 'description' => 'Pembuatan jadwal rutin untuk membersihkan kamar atau fasilitas.', 'icon' => 'cleaning_services'],
            ['name' => 'Laporan Kerusakan', 'key' => 'damage_report', 'description' => 'Mengizinkan penghuni melaporkan kerusakan langsung dari aplikasi.', 'icon' => 'report_problem'],
        ];

        foreach ($facilityFeatures as $feat) {
            FeatureToggle::create([
                'name' => $feat['name'],
                'key' => $feat['key'],
                'description' => $feat['description'],
                'icon' => $feat['icon'],
                'is_active' => true,
                'is_locked' => false,
                'parent_id' => $facilityModule->id,
            ]);
        }

        // 3. FINANCE (Togglable)
        $financeModule = FeatureToggle::create([
            'name' => 'Manajemen Keuangan',
            'key' => 'finance', // Maps to Finance module
            'description' => 'Pengaturan arus kas, invoice, pembayaran, dan pengeluaran kos.',
            'icon' => 'account_balance_wallet',
            'is_active' => true,
            'is_locked' => false,
        ]);

        // Pengeluaran umum: inti, tidak bisa dimatikan
        FeatureToggle::create([
            'name'        => 'Pencatatan Pengeluaran',
            'key'         => 'finance_expense',
            'description' => 'Pencatatan pengeluaran operasional kos (wajib).',
            'icon'        => 'receipt_long',
            'is_active'   => true,
            'is_locked'   => true,
            'parent_id'   => $financeModule->id,
        ]);

        $financeFeatures = [
            ['name' => 'Pembayaran Midtrans (Online)', 'key' => 'finance_midtrans', 'description' => 'Integrasi Midtrans untuk pembayaran Virtual Account, QRIS, dsb.', 'icon' => 'payment'],
            ['name' => 'Pengeluaran Tetap Bulanan', 'key' => 'finance_fixed_expense', 'description' => 'Pengingat dan pencatatan tagihan tetap (PLN, PDAM, WiFi).', 'icon' => 'bolt'],
        ];

        foreach ($financeFeatures as $feat) {
            FeatureToggle::create([
                'name'      => $feat['name'],
                'key'       => $feat['key'],
                'description' => $feat['description'],
                'icon'      => $feat['icon'],
                'is_active' => true,
                'is_locked' => false,
                'parent_id' => $financeModule->id,
            ]);
        }

        // 4. GUEST (Togglable)
        FeatureToggle::create([
            'name' => 'Manajemen Tamu',
            'key' => 'guest', // Maps to Guest module
            'description' => 'Mengizinkan penghuni mendaftarkan tamu dan membebankan tarif tambahan jika perlu.',
            'icon' => 'group_add',
            'is_active' => true,
            'is_locked' => false,
        ]);

        // 5. NOTIFICATION (Togglable)
        $notifModule = FeatureToggle::create([
            'name' => 'Notifikasi Sistem & WhatsApp',
            'key' => 'notification', // Maps to Notification module
            'description' => 'Pengaturan pengiriman notifikasi otomatis via WhatsApp kepada penghuni.',
            'icon' => 'notifications_active',
            'is_active' => true,
            'is_locked' => false,
        ]);

        $notifFeatures = [
            ['name' => 'WhatsApp Struk Pembayaran', 'key' => 'notif_receipt', 'description' => 'Otomatis mengirim WhatsApp bukti pembayaran setelah verifikasi.', 'icon' => 'mark_chat_read'],
            ['name' => 'Pengingat Jatuh Tempo (Reminder)', 'key' => 'notif_due_reminder', 'description' => 'Mengirim WA otomatis H-7, H-3, H-2, H-1, dan hari-H sebelum masa sewa habis.', 'icon' => 'alarm'],
            ['name' => 'Sertakan Link PDF Invoice di WA', 'key' => 'notif_pdf_link', 'description' => 'Menyertakan link download struk PDF di dalam pesan WhatsApp.', 'icon' => 'picture_as_pdf'],
        ];

        foreach ($notifFeatures as $feat) {
            FeatureToggle::create([
                'name' => $feat['name'],
                'key' => $feat['key'],
                'description' => $feat['description'],
                'icon' => $feat['icon'],
                'is_active' => true,
                'is_locked' => false,
                'parent_id' => $notifModule->id,
            ]);
        }
    }
}
