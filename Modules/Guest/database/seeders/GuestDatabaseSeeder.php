<?php

namespace Modules\Guest\database\seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\Guest\Enums\GuestBillStatus;
use Modules\Guest\Enums\GuestRelationship;
use Modules\Guest\Models\Guest;
use Modules\Guest\Models\GuestBill;
use Modules\Schedule\Models\Schedule;

class GuestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (Guest::count() > 0) {
            return;
        }

        $schedules = Schedule::with('room')
            ->where('status', 'active')
            ->get();

        if ($schedules->isEmpty()) {
            $schedules = Schedule::with('room')->get();
        }

        if ($schedules->isEmpty()) {
            return;
        }

        $templates = [
            ['name' => 'Andi Saputra', 'relationship' => GuestRelationship::FRIEND, 'stay_days' => 1],
            ['name' => 'Rina Putri', 'relationship' => GuestRelationship::PARENT, 'stay_days' => 3],
            ['name' => 'Dodi Pratama', 'relationship' => GuestRelationship::RELATIVE, 'stay_days' => 4],
            ['name' => 'Sari Lestari', 'relationship' => GuestRelationship::SIBLING, 'stay_days' => 2],
            ['name' => 'Rahmat Hadi', 'relationship' => GuestRelationship::COLLEAGUE, 'stay_days' => 5],
            ['name' => 'Maya Sari', 'relationship' => GuestRelationship::OTHER, 'stay_days' => 3],
        ];

        $seedStart = Carbon::now()->subDays(12);

        foreach ($schedules as $index => $schedule) {
            $template = $templates[$index % count($templates)];

            $checkIn = (clone $seedStart)->subDays($index);
            $checkOut = (clone $checkIn)->addDays($template['stay_days']);

            $billing = $this->calculateBilling($schedule, $checkIn, $checkOut);

            $guest = Guest::create([
                'schedule_reference_id' => $schedule->id,
                'name' => $template['name'],
                'check_in_at' => $checkIn->toDateTimeString(),
                'check_out_at' => $checkOut->toDateTimeString(),
                'relationship' => $template['relationship']->value,
                'total_days' => $billing['total_days'],
                'billable_days' => $billing['billable_days'],
                'charge_amount' => $billing['charge_amount'],
            ]);

            if ($billing['charge_amount'] > 0) {
                GuestBill::create([
                    'guest_id' => $guest->id,
                    'bill_number' => 'GB-'.now()->format('Ymd').'-'.str_pad((string) $guest->id, 5, '0', STR_PAD_LEFT),
                    'amount' => $billing['charge_amount'],
                    'status' => GuestBillStatus::UNPAID->value,
                ]);
            }
        }
    }

    /**
     * @return array{total_days: int, billable_days: int, charge_amount: float}
     */
    private function calculateBilling(Schedule $schedule, Carbon $checkIn, Carbon $checkOut): array
    {
        $diffHours = ($checkOut->getTimestamp() - $checkIn->getTimestamp()) / 3600;
        $totalDays = (int) ceil($diffHours / 24);
        $billableDays = max(0, $totalDays - 2);

        $roomPrice = (float) ($schedule->room?->price ?? 0);
        $chargeAmount = $billableDays * ($roomPrice * 0.05);

        return [
            'total_days' => $totalDays,
            'billable_days' => $billableDays,
            'charge_amount' => $chargeAmount,
        ];
    }
}
