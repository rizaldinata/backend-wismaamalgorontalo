<?php

namespace Modules\Resident\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\Finance\Events\PaymentSettled::class => [
            \Modules\Resident\Listeners\UpgradeToResident::class,
        ],
        \Modules\Rental\Events\LeaseEnded::class => [
            \Modules\Resident\Listeners\DowngradeToMember::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
