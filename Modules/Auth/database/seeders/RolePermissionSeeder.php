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
        $admin = Role::findByName('admin', 'api');
        $member = Role::findByName('member', 'api');

        $superAdminPermissions = [
            'access-admin-panel',
            'view-dashboard',
            'access-permission-management',
            'view-permission',
            'create-permission',
            'update-permission',
            'delete-permission',
            'access-role-management',
            'view-role',
            'create-role',
            'update-role',
            'delete-role',
            'access-user-management',
            'view-user',
            'create-user',
            'update-user',
            'delete-user',
            'access-room-management',
            'view-room',
            'create-room',
            'update-room',
            'delete-room',
            'view-lease',
            'approve-lease',

            // finance management
            'finance-management-access',
            'finance-dashboard-view',
            'finance-payment-verify',
            'finance-invoice-view',
            'finance-expense-access',
            'finance-expense-view',
            'finance-expense-create',
            'finance-expense-update',
            'finance-expense-delete',
            'access-inventory-management',
            'view-inventory',
            'create-inventory',
            'update-inventory',
            'delete-inventory',
            'access-maintenance-management',
            'view-maintenance',
            'create-maintenance',
            'update-maintenance',
            'delete-maintenance',
            'schedule-maintenance',
            'view-damage-report',
            'access-resident-management',
            'view-resident',
            'create-resident',
            'update-resident',
            'delete-resident',
            'setting-management-access',
            'setting-view',
            'setting-update',
        ];

        $adminPermissions = [
            'access-admin-panel',
            'view-dashboard',
            'access-permission-management',
            'view-permission',
            'create-permission',
            'update-permission',
            'delete-permission',
            'access-role-management',
            'view-role',
            'create-role',
            'update-role',
            'delete-role',
            'access-user-management',
            'view-user',
            'create-user',
            'update-user',
            'delete-user',
            'access-room-management',
            'view-room',
            'create-room',
            'update-room',
            'delete-room',
            'view-lease',
            'approve-lease',
            'finance-management-access',
            'finance-dashboard-view',
            'finance-payment-verify',
            'finance-invoice-view',
            'finance-expense-access',
            'finance-expense-view',
            'finance-expense-create',
            'finance-expense-update',
            'finance-expense-delete',

            // invoice management
            'finance-invoice-view',

            // setting management
            'setting-management-access',
            'setting-view',
            'setting-update',
        ];

        $memberPermissions = [
            'view-room',
            'view-lease',
            'finance-invoice-create',
        ];

        $superAdmin->syncPermissions($superAdminPermissions);
        $admin->syncPermissions($adminPermissions);
        $member->syncPermissions($memberPermissions);

        $this->command->info('Relasi Role dan Permission berhasil disinkronkan!');
    }
}
