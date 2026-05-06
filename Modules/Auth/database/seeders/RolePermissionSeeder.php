<?php

namespace Modules\Auth\database\seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdmin = Role::findByName('super-admin', 'api');
        $admin      = Role::findByName('admin', 'api');
        $member     = Role::findByName('member', 'api');

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
            // Finance
            'finance-dashboard-view',
            'finance-payment-verify',
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
            'finance-payment-verify',
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
        ];

        // ─── Member / Penghuni: akses fitur sebagai penghuni ─────────────
        $memberPermissions = [
            // Room (publik)
            'view-room',
            // Lease (mengajukan sewa)
            'create-lease',
            'view-lease',
            // Finance (tagihan sendiri)
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
        ];

        $superAdmin->syncPermissions($superAdminPermissions);
        $admin->syncPermissions($adminPermissions);
        $member->syncPermissions($memberPermissions);

        $this->command->info('Relasi Role dan Permission berhasil disinkronkan!');
    }
}
