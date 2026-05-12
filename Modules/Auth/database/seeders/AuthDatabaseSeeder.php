<?php

namespace Modules\Auth\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\database\seeders\PermissionSeeder;
use Modules\Auth\database\seeders\RolePermissionSeeder;
use Modules\Auth\database\seeders\RoleSeeder;
use Modules\Auth\database\seeders\UserSeeder;

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
