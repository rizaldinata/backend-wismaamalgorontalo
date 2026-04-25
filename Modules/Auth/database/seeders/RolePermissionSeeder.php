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
        $resident = Role::findByName('resident', 'api');
        $member = Role::findByName('member', 'api');

        $superAdminPermissions = [
            // redirect akses
            'access-admin-panel',

            // dashboard utama
            'view-dashboard',

            // crud permission
            'access-permission-management',
            'view-permission',
            'create-permission',
            'update-permission',
            'delete-permission',

            // crud role
            'access-role-management',
            'view-role',
            'create-role',
            'update-role',
            'delete-role',

            // crud user
            'access-user-management',
            'view-user',
            'create-user',
            'update-user',
            'delete-user',

            // crud room
            'access-room-management',
            'view-room',
            'create-room',
            'update-room',
            'delete-room',

            // sewa menyewa
            'view-lease',
            'approve-lease',
            
            // finance management
            'finance-management-access',
            'finance-dashboard-view',
            'finance-payment-verify',
            'finance-invoice-view',

            // expense management
            'finance-expense-access',
            'finance-expense-view',
            'finance-expense-create',
            'finance-expense-update',
            'finance-expense-delete',

            // setting management
            'setting-management-access',
            'setting-view',
            'setting-update',
        ];
        $adminPermissions = [
            // redirect akses
            'access-admin-panel',

            // dashbboard utama
            'view-dashboard',

            // crud permission
            'access-permission-management',
            'view-permission',
            'create-permission',
            'update-permission',
            'delete-permission',

            // crud role
            'access-role-management',
            'view-role',
            'create-role',
            'update-role',
            'delete-role',

            // crud user
            'access-user-management',
            'view-user',
            'create-user',
            'update-user',
            'delete-user',

            // crud room
            'access-room-management',
            'view-room',
            'create-room',
            'update-room',
            'delete-room',

            // sewa menyewa
            'view-lease',
            'approve-lease',

            // finance management
            'finance-management-access',
            'finance-dashboard-view',
            'finance-payment-verify',

            // expense management
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

        $residentPermissions = [
            'view-room',
            'view-lease',
            'finance-invoice-create'
        ];

        $memberPermissions = [
            'view-room',
            'finance-invoice-create',
        ];

        $superAdmin->syncPermissions($superAdminPermissions);
        $admin->syncPermissions($adminPermissions);
        $resident->syncPermissions($residentPermissions);
        $member->syncPermissions($memberPermissions);

        $this->command->info('Relasi Role dan Permission berhasil disinkronkan!');
    }
}
