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
            // ─── Dashboard ───────────────────────────────────────────────
            ['name' => 'view-dashboard',       'target' => 'dashboard', 'description' => 'Melihat halaman dashboard utama'],

            // ─── Permission Management ────────────────────────────────────
            ['name' => 'view-permission',              'target' => 'permission', 'description' => 'Melihat daftar permission'],
            ['name' => 'create-permission',            'target' => 'permission', 'description' => 'Membuat permission baru'],
            ['name' => 'update-permission',            'target' => 'permission', 'description' => 'Mengubah data permission'],
            ['name' => 'delete-permission',            'target' => 'permission', 'description' => 'Menghapus permission'],

            // ─── Role Management ──────────────────────────────────────────
            ['name' => 'view-role',               'target' => 'role', 'description' => 'Melihat daftar role'],
            ['name' => 'create-role',             'target' => 'role', 'description' => 'Membuat role baru'],
            ['name' => 'update-role',             'target' => 'role', 'description' => 'Mengubah data role'],
            ['name' => 'delete-role',             'target' => 'role', 'description' => 'Menghapus role'],

            // ─── User Management ──────────────────────────────────────────
            ['name' => 'view-user',               'target' => 'user', 'description' => 'Melihat daftar user'],
            ['name' => 'create-user',             'target' => 'user', 'description' => 'Menambah user baru'],
            ['name' => 'update-user',             'target' => 'user', 'description' => 'Mengubah data user'],
            ['name' => 'delete-user',             'target' => 'user', 'description' => 'Menghapus user'],

            // ─── Room Management ──────────────────────────────────────────
            ['name' => 'view-room',               'target' => 'room', 'description' => 'Melihat daftar kamar (termasuk publik)'],
            ['name' => 'create-room',             'target' => 'room', 'description' => 'Menambah kamar baru'],
            ['name' => 'update-room',             'target' => 'room', 'description' => 'Mengubah data kamar'],
            ['name' => 'delete-room',             'target' => 'room', 'description' => 'Menghapus data kamar'],

            // ─── Lease / Sewa ─────────────────────────────────────────────
            ['name' => 'view-lease',    'target' => 'lease', 'description' => 'Melihat daftar sewa / manajemen reservasi'],
            ['name' => 'approve-lease', 'target' => 'lease', 'description' => 'Menyetujui atau menolak pengajuan sewa'],
            ['name' => 'create-lease',  'target' => 'lease', 'description' => 'Mengajukan sewa kamar baru (untuk penghuni)'],

            // ─── Finance ──────────────────────────────────────────────────
            ['name' => 'finance-dashboard-view',    'target' => 'finance', 'description' => 'Melihat dashboard ringkasan keuangan'],
            ['name' => 'finance-payment-verify',    'target' => 'finance', 'description' => 'Memverifikasi pembayaran manual'],
            ['name' => 'finance-invoice-view',      'target' => 'finance', 'description' => 'Melihat seluruh daftar tagihan (admin)'],
            ['name' => 'finance-invoice-create',    'target' => 'finance', 'description' => 'Membuat invoice pembayaran (penghuni)'],
            ['name' => 'finance-expense-view',      'target' => 'finance', 'description' => 'Melihat daftar pengeluaran'],
            ['name' => 'finance-expense-create',    'target' => 'finance', 'description' => 'Membuat pengeluaran baru'],
            ['name' => 'finance-expense-update',    'target' => 'finance', 'description' => 'Mengubah data pengeluaran'],
            ['name' => 'finance-expense-delete',    'target' => 'finance', 'description' => 'Menghapus data pengeluaran'],

            // ─── Inventory ────────────────────────────────────────────────
            ['name' => 'view-inventory',               'target' => 'inventory', 'description' => 'Melihat daftar inventaris'],
            ['name' => 'create-inventory',             'target' => 'inventory', 'description' => 'Menambah barang inventaris'],
            ['name' => 'update-inventory',             'target' => 'inventory', 'description' => 'Mengubah data inventaris'],
            ['name' => 'delete-inventory',             'target' => 'inventory', 'description' => 'Menghapus data inventaris'],

            // ─── Maintenance ──────────────────────────────────────────────
            ['name' => 'view-maintenance',              'target' => 'maintenance', 'description' => 'Melihat jadwal & tugas pemeliharaan'],
            ['name' => 'create-maintenance',            'target' => 'maintenance', 'description' => 'Membuat tugas pemeliharaan (admin)'],
            ['name' => 'update-maintenance',            'target' => 'maintenance', 'description' => 'Memperbarui status pemeliharaan'],
            ['name' => 'delete-maintenance',            'target' => 'maintenance', 'description' => 'Menghapus data pemeliharaan'],
            ['name' => 'schedule-maintenance',          'target' => 'maintenance', 'description' => 'Membuat jadwal pemeliharaan'],
            ['name' => 'view-damage-report',            'target' => 'maintenance', 'description' => 'Melihat semua laporan kerusakan (admin)'],
            ['name' => 'create-damage-report',          'target' => 'maintenance', 'description' => 'Melaporkan kerusakan (penghuni)'],
            ['name' => 'view-my-damage-report',         'target' => 'maintenance', 'description' => 'Melihat laporan kerusakan milik sendiri (penghuni)'],

            // ─── Resident ─────────────────────────────────────────────────
            ['name' => 'view-resident',               'target' => 'resident', 'description' => 'Melihat daftar penghuni'],
            ['name' => 'create-resident',             'target' => 'resident', 'description' => 'Menambah penghuni secara manual'],
            ['name' => 'update-resident',             'target' => 'resident', 'description' => 'Mengubah data penghuni'],
            ['name' => 'delete-resident',             'target' => 'resident', 'description' => 'Menghapus data penghuni'],
            ['name' => 'complete-resident-profile',   'target' => 'resident', 'description' => 'Melengkapi profil untuk menjadi penghuni resmi'],

            // ─── Setting ──────────────────────────────────────────────────
            ['name' => 'setting-view',               'target' => 'setting', 'description' => 'Melihat detail pengaturan'],
            ['name' => 'setting-update',             'target' => 'setting', 'description' => 'Menyimpan perubahan pengaturan'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'api'],
                [
                    'target'      => $permission['target'],
                    'description' => $permission['description'],
                ]
            );
        }

        $this->command->info('PermissionSeeder berhasil dijalankan!');
    }
}
