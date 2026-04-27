<?php

namespace Modules\Resident\database\seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ResidentDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = \Modules\Auth\Models\User::role('member')->take(12)->get();
        $faker = Faker::create('id_ID');

        $residentProfiles = [
            ['name' => 'Rizky Saputra', 'gender' => 'male', 'job' => 'Analis Data', 'city' => 'Kota Gorontalo'],
            ['name' => 'Nur Aisyah Putri', 'gender' => 'female', 'job' => 'Perawat', 'city' => 'Kabupaten Bone Bolango'],
            ['name' => 'Fajar Ramadhan', 'gender' => 'male', 'job' => 'Staff Keuangan', 'city' => 'Kota Gorontalo'],
            ['name' => 'Dewi Maharani', 'gender' => 'female', 'job' => 'Guru SD', 'city' => 'Kabupaten Gorontalo'],
            ['name' => 'Andi Pratama', 'gender' => 'male', 'job' => 'Teknisi Jaringan', 'city' => 'Kabupaten Gorontalo Utara'],
            ['name' => 'Siti Rahmawati', 'gender' => 'female', 'job' => 'Customer Service', 'city' => 'Kota Gorontalo'],
            ['name' => 'Muhammad Ilham', 'gender' => 'male', 'job' => 'Kurir Logistik', 'city' => 'Kabupaten Boalemo'],
            ['name' => 'Nabila Khairunnisa', 'gender' => 'female', 'job' => 'Staf Administrasi', 'city' => 'Kabupaten Pohuwato'],
            ['name' => 'Yusuf Maulana', 'gender' => 'male', 'job' => 'Barista', 'city' => 'Kota Gorontalo'],
            ['name' => 'Maya Oktaviani', 'gender' => 'female', 'job' => 'Desainer Grafis', 'city' => 'Kabupaten Bone Bolango'],
            ['name' => 'Rendi Kurniawan', 'gender' => 'male', 'job' => 'Marketing Executive', 'city' => 'Kabupaten Gorontalo'],
            ['name' => 'Putri Amelia', 'gender' => 'female', 'job' => 'Apoteker', 'city' => 'Kota Gorontalo'],
        ];

        foreach ($members->values() as $index => $user) {
            $profile = $residentProfiles[$index] ?? [
                'name' => $faker->name(),
                'gender' => $faker->randomElement(['male', 'female']),
                'job' => $faker->jobTitle(),
                'city' => 'Kota Gorontalo',
            ];

            // Berikan nama penghuni yang realistis
            $user->update(['name' => $profile['name']]);

            // 1. Buat profil resident
            \Modules\Resident\Models\Resident::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'id_card_number' => $faker->nik(),
                    'phone_number' => '08' . $faker->numerify('##########'),
                    'gender' => $profile['gender'],
                    'job' => $profile['job'],
                    'address_ktp' => $faker->streetAddress() . ', ' . $profile['city'],
                    'emergency_contact_name' => $faker->name(),
                    'emergency_contact_phone' => '08' . $faker->numerify('##########'),
                ]
            );

            // 2. Update Role menjadi resident
            $user->syncRoles(['resident']);
        }
    }
}
