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
        // Ambil 5 member pertama untuk dijadikan penghuni (resident)
        $members = \Modules\Auth\Models\User::role('member')->take(5)->get();

        foreach ($members as $user) {
            // 1. Buat profil resident
            \Modules\Resident\Models\Resident::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'id_card_number' => fake()->unique()->numerify('################'),
                    'phone_number' => fake()->numerify('08##########'),
                    'gender' => fake()->randomElement(['male', 'female']),
                    'job' => fake()->jobTitle(),
                    'address_ktp' => fake()->address(),
                    'emergency_contact_name' => fake()->name(),
                    'emergency_contact_phone' => fake()->numerify('08##########'),
                ]
            );

            // 2. Update Role menjadi resident
            $user->syncRoles(['resident']);
        }
    }
}
