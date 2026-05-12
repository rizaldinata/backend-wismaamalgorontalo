<?php

namespace Modules\Resident\Listeners;

use Modules\Finance\Events\PaymentSettled;
use Modules\Auth\Models\User;
use Spatie\Permission\Models\Role;

class UpgradeToResident
{
    /**
     * Handle the event.
     */
    public function handle(PaymentSettled $event): void
    {
        $payment = $event->payment;
        $invoice = $payment->invoice;
        $lease = $invoice->lease;

        if (!$lease) return;

        $resident = $lease->resident;
        if (!$resident) return;

        $user = $resident->user;
        if (!$user) return;

        // Pastikan role resident ada
        if (!Role::where('name', 'resident')->exists()) {
            Role::create(['name' => 'resident', 'guard_name' => 'api']);
        }

        // Lepas role member, assign resident
        if ($user->hasRole('member')) {
            $user->removeRole('member');
        }
        if (!$user->hasRole('resident')) {
            $user->assignRole('resident');
        }
    }
}
