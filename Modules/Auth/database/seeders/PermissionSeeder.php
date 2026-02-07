<?php

namespace Modules\Auth\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // access redirect logic
            ['name' => 'access-admin-panel', 'target' => 'admin', 'description' => 'Akses ke panel admin'],
            ['name' => 'access-resident-area', 'target' => 'user', 'description' => 'Akses ke area penghuni'],

            // sidebar dahsboard utama
            ['name' => 'view-dashboard', 'target' => 'admin', 'description' => 'Melihat dashboard utama'],

            // crud permission
            ['name' => 'access-permission-management', 'target' => 'admin', 'description' => 'Akses manajemen permission'],
            ['name' => 'view-permission', 'target' => 'admin', 'description' => 'Melihat daftar permission'],
            ['name' => 'create-permission', 'target' => 'admin', 'description' => 'Membuat permission baru'],
            ['name' => 'update-permission', 'target' => 'admin', 'description' => 'Mengubah data permission'],
            ['name' => 'delete-permission', 'target' => 'admin', 'description' => 'Menghapus permission'],

            // crud user
            ['name' => 'access-user-management', 'target' => 'admin', 'description' => 'Akses manajemen user'],
            ['name' => 'view-user', 'target' => 'admin', 'description' => 'Melihat daftar user'],
            ['name' => 'create-user', 'target' => 'admin', 'description' => 'Menambah user baru'],
            ['name' => 'update-user', 'target' => 'admin', 'description' => 'Mengubah data user'],
            ['name' => 'delete-user', 'target' => 'admin', 'description' => 'Menghapus user'],

            // crud room
            ['name' => 'access-room-management', 'target' => 'admin', 'description' => 'Akses manajemen kamar'],
            ['name' => 'view-room', 'target' => 'admin', 'description' => 'Melihat daftar kamar'],
            ['name' => 'create-room', 'target' => 'admin', 'description' => 'Menambah kamar baru'],
            ['name' => 'update-room', 'target' => 'admin', 'description' => 'Mengubah data kamar'],
            ['name' => 'delete-room', 'target' => 'admin', 'description' => 'Menghapus data kamar'],

            // Lease Management
            ['name' => 'view-lease', 'target' => 'admin', 'description' => 'Melihat daftar penyewaan'],
            ['name' => 'approve-lease', 'target' => 'admin', 'description' => 'Menyetujui atau menolak penyewaan'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'api'],
                [
                    'target' => $permission['target'],
                    'description' => $permission['description']
                ]
            );
        }

        $this->command->info('PermissionSeeder berhasil dijalankan!');
    }
}
