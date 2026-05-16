<?php

namespace Modules\Auth\database\seeders;

use Illuminate\Database\Seeder;

class AuthDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
        ]);
    }
}
