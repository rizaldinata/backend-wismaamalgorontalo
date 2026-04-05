<?php

namespace Modules\Rental\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Rental\Models\Lease;
use Modules\Rental\Enums\{LeaseStatus, RentalType};

class LeaseFactory extends Factory
{
    protected $model = Lease::class;

    public function definition(): array
    {
        return [
            'resident_id' => \Modules\Resident\Models\Resident::factory(),
            'room_id' => \Modules\Room\Models\Room::factory(),
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'rental_type' => \Modules\Rental\Enums\RentalType::MONTHLY,
            'status' => \Modules\Rental\Enums\LeaseStatus::PENDING,
        ];
    }
}
