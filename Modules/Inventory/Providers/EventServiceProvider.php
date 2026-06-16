<?php

namespace Modules\Inventory\Providers;

use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Inventory\Listeners\BuatChecklistInventarisSetelahSewaSelesai;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JadwalSewaSelesai::class => [
            BuatChecklistInventarisSetelahSewaSelesai::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
