<?php

namespace Modules\Room\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Room\Enums\RoomStatus;
use Modules\Room\Models\Room;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numerify('Room-###'),
            'title' => fake()->words(3, true),
            'price' => fake()->randomElement([500000, 750000, 1000000]),
            'description' => fake()->paragraph(),
            'status' => RoomStatus::AVAILABLE,
            'facilities' => ['AC', 'WiFi', 'Kasur'],
        ];
    }
}
