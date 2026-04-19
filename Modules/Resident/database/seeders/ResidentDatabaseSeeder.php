<?php

namespace Modules\Resident\database\seeders;

use Illuminate\Database\Seeder;

class ResidentDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = \Modules\Auth\Models\User::role('member')->get();

        foreach ($members as $user) {
            \Modules\Resident\Models\Resident::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'id_card_number' => fake()->unique()->numerify('################'),
                    'phone_number' => $user->phone_number ?? fake()->phoneNumber(),
                    'gender' => fake()->randomElement(['male', 'female']),
                    'job' => fake()->jobTitle(),
                    'address_ktp' => fake()->address(),
                    'emergency_contact_name' => fake()->name(),
                    'emergency_contact_phone' => fake()->phoneNumber(),
                ]
            );
        }
    }
}
