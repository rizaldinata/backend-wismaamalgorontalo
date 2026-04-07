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
            // sidebar dahsboard utama
            ['name' => 'access-admin-panel', 'target' => 'admin', 'description' => 'Akses dashboard admin'],
            ['name' => 'view-dashboard', 'target' => 'admin', 'description' => 'Melihat dashboard utama'],

            // crud permission
            ['name' => 'access-permission-management', 'target' => 'admin', 'description' => 'Akses manajemen permission'],
            ['name' => 'view-permission', 'target' => 'admin', 'description' => 'Melihat daftar permission'],
            ['name' => 'create-permission', 'target' => 'admin', 'description' => 'Membuat permission baru'],
            ['name' => 'update-permission', 'target' => 'admin', 'description' => 'Mengubah data permission'],
            ['name' => 'delete-permission', 'target' => 'admin', 'description' => 'Menghapus permission'],

            // crud roles
            [
                'name' => 'access-role-management',
                'target' => 'Admin',
                'description' => 'Memberikan akses untuk melihat menu Role di Dashboard'
            ],
            [
                'name' => 'view-role',
                'target' => 'Admin',
                'description' => 'Melihat daftar role'
            ],
            [
                'name' => 'create-role',
                'target' => 'Admin',
                'description' => 'Menambah role baru'
            ],
            [
                'name' => 'update-role',
                'target' => 'Admin',
                'description' => 'Mengubah data role'
            ],
            [
                'name' => 'delete-role',
                'target' => 'Admin',
                'description' => 'Menghapus role'
            ],

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

            // Permission khusus untuk role Resident (tidak boleh diassign ke role lain)
            ['name' => 'pay_lease_bill', 'target' => 'resident', 'description' => 'Izin untuk membayar tagihan sewa'],
            // Area khusus penhuni (untuk dipancing menunya)
            ['name' => 'access-resident-area', 'target' => 'resident', 'description' => 'Akses area khusus penghuni'],

            // Finance managemet
            ['name' => 'finance-management-access', 'target' => 'admin', 'description' => 'Akses manajemen keuangan'],
            ['name' => 'finance-dashboard-view', 'target' => 'admin', 'description' => 'Untuk melihat dashboard module finance'],
            ['name' => 'finance-payment-verify', 'target' => 'admin', 'description' => 'Untuk memverifikasi pembayaran yang dilakukan secara manual'],
            ['name' => 'finance-invoice-create', 'target' => 'user', 'description' => 'Untuk pengguna membuat invoice pembayaran baru'],

            ['name' => 'finance-expense-access', 'target' => 'admin', 'description' => 'Akses manajemen pengeluaran'],
            ['name' => 'finance-expense-view', 'target' => 'admin', 'description' => 'Untuk admin melihat pengeluaran'],
            ['name' => 'finance-expense-create', 'target' => 'admin', 'description' => 'Untuk admin membuat pengeluaran baru'],
            ['name' => 'finance-expense-update', 'target' => 'admin', 'description' => 'Untuk admin mengubah pengeluaran'],
            ['name' => 'finance-expense-delete', 'target' => 'admin', 'description' => 'Untuk admin menghapus pengeluaran'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'api'],
                [
                    'target' => $permission['target'],
                    'description' => $permission['description'],
                ]
            );
        }

        $this->command->info('PermissionSeeder berhasil dijalankan!');
    }
}
