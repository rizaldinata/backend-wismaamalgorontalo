<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Resident\Models\Resident;
use Modules\Room\Models\Room;
use Modules\Rental\Models\Lease;
use Modules\Finance\Models\Invoice;
use Carbon\Carbon;

class ResidentAdminSeeder extends Seeder
{
    public function run()
    {
        // 1. Buat Kamar (AC dan Non AC dengan harga rata 500.000)
        $room1 = Room::create([
            'title' => 'Kamar AC', 
            'number' => 'AC01', 
            'price' => 500000, 
            'status' => 'occupied'
        ]);
        
        $room2 = Room::create([
            'title' => 'Kamar Non AC', 
            'number' => 'NONAC01', 
            'price' => 500000, 
            'status' => 'available' // Tetap available untuk stat card 'Kamar Tersedia'
        ]);

        // 2. Buat User & Profile Resident
        $user1 = User::create(['name' => 'Dwi Rahmawati', 'email' => 'dwi@test.com', 'password' => bcrypt('password')]);
        Resident::create([
            'user_id' => $user1->id, 
            'id_card_number' => '35150001', 
            'phone_number' => '0812-3456-7890', 
            'gender' => 'female'
        ]);

        $user2 = User::create(['name' => 'Ahmad Budi', 'email' => 'budi@test.com', 'password' => bcrypt('password')]);
        Resident::create([
            'user_id' => $user2->id, 
            'id_card_number' => '35150002', 
            'phone_number' => '0899-8888-7777', 
            'gender' => 'male'
        ]);

        // 3. Buat Kontrak Sewa (Skenario: Dwi Aktif Belum Lunas, Budi Pending)
        $lease1 = Lease::create([
            'user_id' => $user1->id, 
            'room_id' => $room1->id, 
            'start_date' => Carbon::now()->subMonths(2), 
            'end_date' => Carbon::now()->addMonths(10), 
            'status' => 'active'
        ]);

        $lease2 = Lease::create([
            'user_id' => $user2->id, 
            'room_id' => $room2->id, 
            'start_date' => Carbon::now(), 
            'end_date' => Carbon::now()->addMonths(6), 
            'status' => 'pending'
        ]);

        // 4. Buat Tagihan (Invoices) - Amount disesuaikan menjadi 500.000
        Invoice::create([
            'lease_id' => $lease1->id,
            'invoice_number' => 'INV-001',
            'amount' => 500000,
            'status' => 'unpaid', // Dwi belum bayar bulan ini
            'due_date' => Carbon::now()->addDays(5)
        ]);

        Invoice::create([
            'lease_id' => $lease2->id,
            'invoice_number' => 'INV-002',
            'amount' => 500000,
            'status' => 'unpaid', // Budi baru booking, masih pending & belum bayar
            'due_date' => Carbon::now()->addDays(1)
        ]);
    }
}