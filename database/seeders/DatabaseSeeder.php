<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\database\seeders\AuthDatabaseSeeder;
use Modules\Finance\database\seeders\FinanceDatabaseSeeder;
use Modules\Rental\database\seeders\RentalDatabaseSeeder;
use Modules\Resident\database\seeders\ResidentDatabaseSeeder;
use Modules\Room\database\seeders\RoomDatabaseSeeder;
use Modules\Setting\database\seeders\SettingDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AuthDatabaseSeeder::class,
            SettingDatabaseSeeder::class,
            RoomDatabaseSeeder::class,
            ResidentDatabaseSeeder::class,
            RentalDatabaseSeeder::class,
            FinanceDatabaseSeeder::class,
        ]);
    }
}
