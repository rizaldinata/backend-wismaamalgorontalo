<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Resident\Models\Lease;
use Modules\Resident\Models\Resident;
use Modules\Room\Models\Room;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        $admin = User::create([
            'name' => 'Admin Wisma Amal',
            'email' => 'admin@wismaamal.com',
            'password' => bcrypt('password'),
        ]);
        $admin->email_verified_at = now();
        $admin->save();
        $admin->assignRole('super-admin');

        // Create Admin Staff User (role: admin)
        $staff1 = User::create([
            'name' => 'Staff Admin',
            'email' => 'staff@wismaamal.com',
            'password' => bcrypt('password'),
        ]);
        $staff1->email_verified_at = now();
        $staff1->save();
        $staff1->assignRole('admin');

        // Create sample Guest User (calon penghuni yang belum sewa)
        $guest = User::create([
            'name' => 'Guest User',
            'email' => 'guest@wismaamal.com',
            'password' => bcrypt('password'),
        ]);
        $guest->email_verified_at = now();
        $guest->save();
        $guest->assignRole('guest');

        // Create Resident Users with their resident profile
        $residentsData = [
            [
                'name' => 'Ahmad Hidayat',
                'email' => 'ahmad@example.com',
                'id_card_number' => '7501012901950001',
                'phone_number' => '081234567890',
                'gender' => 'male',
                'job' => 'Karyawan Swasta',
                'address_ktp' => 'Jl. Merdeka No. 123, Gorontalo',
                'emergency_contact_name' => 'Fatimah Hidayat',
                'emergency_contact_phone' => '081234567891',
            ],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti@example.com',
                'id_card_number' => '7501016505920002',
                'phone_number' => '081234567892',
                'gender' => 'female',
                'job' => 'Guru',
                'address_ktp' => 'Jl. Pendidikan No. 45, Gorontalo',
                'emergency_contact_name' => 'Ahmad Nurhaliza',
                'emergency_contact_phone' => '081234567893',
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'id_card_number' => '7501011203880003',
                'phone_number' => '081234567894',
                'gender' => 'male',
                'job' => 'Mahasiswa',
                'address_ktp' => 'Jl. Kampus No. 67, Gorontalo',
                'emergency_contact_name' => 'Siti Santoso',
                'emergency_contact_phone' => '081234567895',
            ],
            [
                'name' => 'Dewi Lestari',
                'email' => 'dewi@example.com',
                'id_card_number' => '7501015204930004',
                'phone_number' => '081234567896',
                'gender' => 'female',
                'job' => 'Perawat',
                'address_ktp' => 'Jl. Kesehatan No. 89, Gorontalo',
                'emergency_contact_name' => 'Budi Lestari',
                'emergency_contact_phone' => '081234567897',
            ],
            [
                'name' => 'Rudi Hartono',
                'email' => 'rudi@example.com',
                'id_card_number' => '7501013108900005',
                'phone_number' => '081234567898',
                'gender' => 'male',
                'job' => 'Wiraswasta',
                'address_ktp' => 'Jl. Perdagangan No. 12, Gorontalo',
                'emergency_contact_name' => 'Ani Hartono',
                'emergency_contact_phone' => '081234567899',
            ],
            [
                'name' => 'Indah Permata',
                'email' => 'indah@example.com',
                'id_card_number' => '7501017809940006',
                'phone_number' => '081234567800',
                'gender' => 'female',
                'job' => 'Dokter',
                'address_ktp' => 'Jl. Rumah Sakit No. 34, Gorontalo',
                'emergency_contact_name' => 'Hadi Permata',
                'emergency_contact_phone' => '081234567801',
            ],
            [
                'name' => 'Agus Wijaya',
                'email' => 'agus@example.com',
                'id_card_number' => '7501012405910007',
                'phone_number' => '081234567802',
                'gender' => 'male',
                'job' => 'PNS',
                'address_ktp' => 'Jl. Pemerintahan No. 56, Gorontalo',
                'emergency_contact_name' => 'Sri Wijaya',
                'emergency_contact_phone' => '081234567803',
            ],
            [
                'name' => 'Maya Kusuma',
                'email' => 'maya@example.com',
                'id_card_number' => '7501016612950008',
                'phone_number' => '081234567804',
                'gender' => 'female',
                'job' => 'Dosen',
                'address_ktp' => 'Jl. Universitas No. 78, Gorontalo',
                'emergency_contact_name' => 'Rudi Kusuma',
                'emergency_contact_phone' => '081234567805',
            ],
        ];

        $users = [];
        foreach ($residentsData as $residentData) {
            $user = User::create([
                'name' => $residentData['name'],
                'email' => $residentData['email'],
                'password' => bcrypt('password'),
            ]);
            $user->email_verified_at = now();
            $user->save();
            $user->assignRole('resident');

            Resident::create([
                'user_id' => $user->id,
                'id_card_number' => $residentData['id_card_number'],
                'phone_number' => $residentData['phone_number'],
                'gender' => $residentData['gender'],
                'job' => $residentData['job'],
                'address_ktp' => $residentData['address_ktp'],
                'emergency_contact_name' => $residentData['emergency_contact_name'],
                'emergency_contact_phone' => $residentData['emergency_contact_phone'],
            ]);

            $users[] = $user;
        }

        // Create Rooms
        $roomsData = [
            // Lantai 1
            ['number' => '101', 'type' => 'Single', 'price' => '500000', 'description' => 'Kamar single lantai 1, dekat tangga'],
            ['number' => '102', 'type' => 'Single', 'price' => '500000', 'description' => 'Kamar single lantai 1'],
            ['number' => '103', 'type' => 'Double', 'price' => '800000', 'description' => 'Kamar double lantai 1'],
            ['number' => '104', 'type' => 'Single', 'price' => '500000', 'description' => 'Kamar single lantai 1, dekat kamar mandi'],
            ['number' => '105', 'type' => 'Single', 'price' => '500000', 'description' => 'Kamar single lantai 1'],

            // Lantai 2
            ['number' => '201', 'type' => 'Single', 'price' => '550000', 'description' => 'Kamar single lantai 2, dekat tangga'],
            ['number' => '202', 'type' => 'Double', 'price' => '850000', 'description' => 'Kamar double lantai 2'],
            ['number' => '203', 'type' => 'Single', 'price' => '550000', 'description' => 'Kamar single lantai 2'],
            ['number' => '204', 'type' => 'Double', 'price' => '850000', 'description' => 'Kamar double lantai 2, menghadap depan'],
            ['number' => '205', 'type' => 'Single', 'price' => '550000', 'description' => 'Kamar single lantai 2'],

            // Lantai 3
            ['number' => '301', 'type' => 'Single', 'price' => '600000', 'description' => 'Kamar single lantai 3, dekat tangga'],
            ['number' => '302', 'type' => 'Single', 'price' => '600000', 'description' => 'Kamar single lantai 3'],
            ['number' => '303', 'type' => 'Double', 'price' => '900000', 'description' => 'Kamar double lantai 3, view bagus'],
            ['number' => '304', 'type' => 'Single', 'price' => '600000', 'description' => 'Kamar single lantai 3'],
            ['number' => '305', 'type' => 'Suite', 'price' => '1200000', 'description' => 'Kamar suite lantai 3, dengan kamar mandi dalam'],
        ];

        $rooms = [];
        foreach ($roomsData as $roomData) {
            $rooms[] = Room::create($roomData);
        }

        // Create Leases
        // Active leases
        Lease::create([
            'user_id' => $users[0]->id,
            'room_id' => $rooms[0]->id,
            'start_date' => now()->subMonths(3),
            'end_date' => null,
            'price_per_month' => 500000,
            'status' => 'active',
        ]);

        Lease::create([
            'user_id' => $users[1]->id,
            'room_id' => $rooms[2]->id,
            'start_date' => now()->subMonths(6),
            'end_date' => null,
            'price_per_month' => 800000,
            'status' => 'active',
        ]);

        Lease::create([
            'user_id' => $users[2]->id,
            'room_id' => $rooms[5]->id,
            'start_date' => now()->subMonths(2),
            'end_date' => null,
            'price_per_month' => 550000,
            'status' => 'active',
        ]);

        Lease::create([
            'user_id' => $users[3]->id,
            'room_id' => $rooms[8]->id,
            'start_date' => now()->subMonth(),
            'end_date' => null,
            'price_per_month' => 850000,
            'status' => 'active',
        ]);

        Lease::create([
            'user_id' => $users[4]->id,
            'room_id' => $rooms[11]->id,
            'start_date' => now()->subMonths(4),
            'end_date' => null,
            'price_per_month' => 600000,
            'status' => 'active',
        ]);

        // Pending leases
        Lease::create([
            'user_id' => $users[5]->id,
            'room_id' => $rooms[14]->id,
            'start_date' => now()->addDays(7),
            'end_date' => null,
            'price_per_month' => 1200000,
            'status' => 'pending',
        ]);

        Lease::create([
            'user_id' => $users[6]->id,
            'room_id' => $rooms[6]->id,
            'start_date' => now()->addDays(5),
            'end_date' => null,
            'price_per_month' => 850000,
            'status' => 'pending',
        ]);

        // Finished/ended leases (use cancelled status to match enum)
        Lease::create([
            'user_id' => $users[7]->id,
            'room_id' => $rooms[1]->id,
            'start_date' => now()->subYear(),
            'end_date' => now()->subMonths(2),
            'price_per_month' => 500000,
            'status' => 'cancelled',
        ]);

        // Update room statuses based on active leases
        $rooms[0]->update(['status' => 'occupied']);
        $rooms[2]->update(['status' => 'occupied']);
        $rooms[5]->update(['status' => 'occupied']);
        $rooms[8]->update(['status' => 'occupied']);
        $rooms[11]->update(['status' => 'occupied']);

        // Set some rooms to maintenance
        $rooms[4]->update(['status' => 'maintenance']);
        $rooms[9]->update(['status' => 'maintenance']);

        $this->command->info('Dummy data seeder completed successfully!');
        $this->command->info('Admin: admin@wismaamal.com / password');
        $this->command->info('Staff: staff@wismaamal.com / password');
        $this->command->info('Residents: ahmad@example.com (and others) / password');
    }
}
