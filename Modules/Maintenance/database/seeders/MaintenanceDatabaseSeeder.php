<?php

namespace Modules\Maintenance\database\seeders;

use Illuminate\Database\Seeder;

class MaintenanceDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            MaintenanceRequestSeeder::class,
        ]);
    }
}
