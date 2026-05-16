<?php

namespace Modules\Auth\database\seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdmin = Role::findByName('super-admin', 'api');
        $admin = Role::findByName('admin', 'api');
        $member = Role::findByName('member', 'api');

        // ─── Super Admin: akses penuh ke semua modul ─────────────────────
        $superAdminPermissions = [
            // Dashboard
            'view-dashboard',
            // Permission Management
            'view-permission',
            'create-permission',
            'update-permission',
            'delete-permission',
            // Role Management
            'view-role',
            'create-role',
            'update-role',
            'delete-role',
            // User Management
            'view-user',
            'create-user',
            'update-user',
            'delete-user',
            // Room Management
            'view-room',
            'create-room',
            'update-room',
            'delete-room',
            // Lease
            'view-lease',
            'approve-lease',
            'create-lease',
            'extend-lease',
            // Finance
            'finance-dashboard-view',
            'finance-payment-verify',
            'finance-payment-view',
            'finance-invoice-view',
            'finance-invoice-create',
            'finance-expense-view',
            'finance-expense-create',
            'finance-expense-update',
            'finance-expense-delete',
            // Inventory
            'view-inventory',
            'create-inventory',
            'update-inventory',
            'delete-inventory',
            // Maintenance
            'view-maintenance',
            'create-maintenance',
            'update-maintenance',
            'delete-maintenance',
            'schedule-maintenance',
            'view-damage-report',
            'create-damage-report',
            'view-my-damage-report',
            // Resident
            'view-resident',
            'create-resident',
            'update-resident',
            'delete-resident',
            // Setting
            'setting-view',
            'setting-update',
            // Guest
            'view-guest',
            'view-my-guest',
            'create-guest',
            'delete-guest',
            'pay-guest-bill',
            'verify-guest-bill',
            'view-guest-bill',
        ];

        // ─── Admin: manajemen operasional, tanpa akses setting sistem ─────
        $adminPermissions = [
            // Dashboard
            'view-dashboard',
            // Permission Management
            'view-permission',
            'create-permission',
            'update-permission',
            'delete-permission',
            // Role Management
            'view-role',
            'create-role',
            'update-role',
            'delete-role',
            // User Management
            'view-user',
            'create-user',
            'update-user',
            'delete-user',
            // Room Management
            'view-room',
            'create-room',
            'update-room',
            'delete-room',
            // Lease
            'view-lease',
            'approve-lease',
            // Finance
            'finance-dashboard-view',
            'finance-payment-view',
            'finance-payment-verify',
            'finance-payment-view',
            'finance-invoice-view',
            'finance-expense-view',
            'finance-expense-create',
            'finance-expense-update',
            'finance-expense-delete',
            // Inventory
            'view-inventory',
            'create-inventory',
            'update-inventory',
            'delete-inventory',
            // Maintenance (admin lihat semua)
            'view-maintenance',
            'create-maintenance',
            'update-maintenance',
            'delete-maintenance',
            'schedule-maintenance',
            'view-damage-report',
            'view-my-damage-report',
            // Resident
            'view-resident',
            'create-resident',
            'update-resident',
            'delete-resident',
            // Setting
            'setting-view',
            'setting-update',
            // Guest
            'view-guest',
            'create-guest',
            'verify-guest-bill',
            'view-guest-bill',
        ];

        // ─── Member: tamu terdaftar, belum memiliki kamar ────────────────

        $memberPermissions = [
            // Room (browsing kamar)
            'view-room',
            // Lease (mengajukan sewa kamar baru, bukan perpanjang)
            'create-lease',
            // Finance (bayar invoice sewa baru + riwayat keuangan dari sewa sebelumnya)
            'finance-invoice-create',
            'finance-me-summary-view',
            'finance-me-invoice-view',
            'finance-me-payment-view',
            // Profil (melengkapi biodata KTP sebelum sewa)
            'complete-resident-profile',
            // Guest (menambahkan dan mengelola tamu)
            'view-my-guest',
            'create-guest',
            'delete-guest',
            'pay-guest-bill',
        ];

        // ─── Resident: penghuni aktif dengan sewa yang sudah dibayar ─────
        $resident = Role::firstOrCreate(['name' => 'resident', 'guard_name' => 'api']);
        $residentPermissions = [
            // Room (masih bisa lihat kamar)
            'view-room',
            // Lease (melihat sewa sendiri, buat sewa baru, perpanjang sewa aktif)
            'view-lease',
            'create-lease',
            'extend-lease',
            // Finance (tagihan & pembayaran sendiri)
            'finance-invoice-create',
            'finance-me-summary-view',
            'finance-me-invoice-view',
            'finance-me-payment-view',
            // Maintenance & Damage Report (laporan sendiri)
            'view-maintenance',
            'create-damage-report',
            'view-my-damage-report',
            // Profil
            'complete-resident-profile',
            // Guest
            'view-my-guest',
            'create-guest',
            'delete-guest',
            'pay-guest-bill',
        ];

        $superAdmin->syncPermissions($superAdminPermissions);
        $admin->syncPermissions($adminPermissions);
        $member->syncPermissions($memberPermissions);
        $resident->syncPermissions($residentPermissions);

        $this->command->info('Relasi Role dan Permission berhasil disinkronkan!');
    }
}
