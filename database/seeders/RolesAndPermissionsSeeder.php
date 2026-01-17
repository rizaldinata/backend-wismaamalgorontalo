<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Reset Cached Roles & Permissions
        // Penting agar tidak ada cache permission lama yang tertinggal
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Buat Permission (Atomic / Granular)
        // Kita definisikan permission sekecil mungkin agar fleksibel
        $permissions = [
            // --- Access Control (Kunci Redirect Logic) ---
            'access_admin_panel',   // Jika punya ini -> Redirect ke Admin Dashboard
            'access_resident_area', // Jika punya ini -> Redirect ke Landing Page User

            // --- Room Features ---
            'view_rooms',
            'manage_rooms',         // Create, Update, Delete Rooms

            // --- Lease/Transaction Features ---
            'apply_lease',          // Mengajukan sewa
            'pay_lease_bill',       // Bayar tagihan
            'manage_leases',        // Approve/Reject sewa (Admin)
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // 3. Buat Role & Assign Permission (Bundling)

        // ROLE: GUEST (Calon Penghuni)
        // Logic: Login -> Cek permission -> Tidak punya 'access_admin_panel' -> Redirect Landing Page
        $guest = Role::create(['name' => 'member']);
        $guest->givePermissionTo([
            'access_resident_area',
            'view_rooms',
            'apply_lease',
        ]);

        // ROLE: RESIDENT (Penghuni Kost)
        // Logic: Login -> Cek permission -> Tidak punya 'access_admin_panel' -> Redirect Landing Page (Tapi fitur bayar terbuka)
        $resident = Role::create(['name' => 'resident']);
        $resident->givePermissionTo([
            'access_resident_area',
            'view_rooms',
            'pay_lease_bill',
        ]);

        // ROLE: ADMIN (Pengelola)
        // Logic: Login -> Cek permission -> Punya 'access_admin_panel' -> Redirect Dashboard
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'access_admin_panel',
            'manage_rooms',
            'manage_leases',
            'view_rooms',
        ]);

        // ROLE: SUPER ADMIN
        // Logic: Full Akses
        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());
    }
}
