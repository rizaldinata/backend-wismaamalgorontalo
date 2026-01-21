<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Room\Database\Seeders\RoomDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(UserSeeder::class);
    }
}
