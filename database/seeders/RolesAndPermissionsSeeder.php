<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Buat Permissions (Hak Akses Spesifik)
        // Contoh untuk Module Room
        Permission::create(['name' => 'view rooms']);
        Permission::create(['name' => 'create rooms']);
        Permission::create(['name' => 'edit rooms']);
        Permission::create(['name' => 'delete rooms']);

        // Contoh untuk Module Finance
        Permission::create(['name' => 'view finance']);
        Permission::create(['name' => 'approve payment']);

        // 2. Buat Roles & Assign Permissions

        // A. USER BIASA (GUEST)
        // User yang baru register lewat Flutter, belum sewa kamar.
        // Biasanya minim akses, atau tidak perlu role khusus (hanya auth user).
        $roleGuest = Role::create(['name' => 'guest']);
        $roleGuest->givePermissionTo('view rooms');

        // B. PENGHUNI (RESIDENT)
        // User yang sah sedang menyewa kamar.
        $roleResident = Role::create(['name' => 'resident']);
        $roleResident->givePermissionTo(['view rooms']);
        // Nanti ditambah: 'create maintenance ticket', 'view own invoice'

        // C. STAFF (CUSTOM ADMIN)
        // Contoh: Staff Kebersihan / Maintenance
        $roleMaintenance = Role::create(['name' => 'maintenance-staff']);
        // Nanti ditambah permission modul maintenance

        // D. SUPER ADMIN
        // Bisa segalanya (God Mode)
        $roleSuperAdmin = Role::create(['name' => 'super-admin']);
        // Super admin tidak perlu di-assign permission satu per satu
        // Kita atur nanti di AuthServiceProvider agar dia bisa bypass semuanya.
    }
}
