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
        ];

        $residentPermissions = [
            'access-resident-area',
            'view-room',
            'view-lease',
        ];

        $memberPermissions = [
            'access-resident-area',
            'view-room',
        ];

        $superAdmin->syncPermissions($superAdminPermissions);
        $admin->syncPermissions($adminPermissions);
        $resident->syncPermissions($residentPermissions);
        $member->syncPermissions($memberPermissions);

        $this->command->info('Relasi Role dan Permission berhasil disinkronkan!');
    }
}
