<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\database\seeders\AuthDatabaseSeeder;
use Modules\Finance\database\seeders\FinanceDatabaseSeeder;
use Modules\Room\database\seeders\RoomDatabaseSeeder;
use Modules\Setting\database\seeders\SettingDatabaseSeeder;
use Modules\Inventory\database\seeders\InventoryDatabaseSeeder;
use Modules\Maintenance\database\seeders\MaintenanceDatabaseSeeder;
use Modules\Guest\database\seeders\GuestDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AuthDatabaseSeeder::class,
            SettingDatabaseSeeder::class,
            RoomDatabaseSeeder::class,
            \Modules\Room\database\seeders\RoomImagePlaceholderSeeder::class,
            GuestDatabaseSeeder::class,
            InventoryDatabaseSeeder::class,
            MaintenanceDatabaseSeeder::class,
            FinanceDatabaseSeeder::class,
        ]);
    }
}
