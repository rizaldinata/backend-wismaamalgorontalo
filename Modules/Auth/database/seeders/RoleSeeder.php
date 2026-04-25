<?php

namespace Modules\Auth\database\seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(['name' => 'super-admin', 'guard_name' => 'api', 'description' => 'Admin utama / owner kost']);
        Role::updateOrCreate(['name' => 'admin', 'guard_name' => 'api', 'description' => 'admin pengelola kost']);
        Role::updateOrCreate(['name' => 'member', 'guard_name' => 'api', 'description' => 'pengguna aplikasi yang belum menjadi penghuni kost']);
    }
}
