<?php

namespace Modules\Resident\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Resident\Models\Resident;

class ResidentFactory extends Factory
{

    protected $model = Resident::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'id_card_number' => fake()->unique()->numerify('################'),
            'phone_number' => fake()->phoneNumber(),
            'gender' => fake()->randomElement(['male', 'female']),
            'job' => fake()->jobTitle(),
            'address_ktp' => fake()->address(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
        ];
    }
}
