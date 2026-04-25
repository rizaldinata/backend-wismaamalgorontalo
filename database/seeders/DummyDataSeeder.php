<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;
use Modules\Rental\Models\Lease;
use Modules\Resident\Models\Resident;
use Modules\Room\Models\Room;
use Modules\Room\Models\RoomImage;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure directories exist
        if (!Storage::disk('public')->exists('rooms')) {
            Storage::disk('public')->makeDirectory('rooms');
        }
        if (!Storage::disk('public')->exists('payments')) {
            Storage::disk('public')->makeDirectory('payments');
        }

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
        $guest->assignRole('member');

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
            $user->assignRole('member');

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
            [
                'number' => '101',
                'title' => 'Standard Deluxe 101',
                'type' => 'Standard',
                'price' => 500000,
                'status' => 'occupied',
                'description' => 'Kamar Standard Deluxe yang dirancang khusus untuk kenyamanan maksimal Anda. Menawarkan suasana tenang dengan pencahayaan alami yang cukup. Dilengkapi dengan fasilitas modern, area kerja yang ergonomis, dan koneksi internet cepat.',
                'facilities' => ['AC', 'WiFi', 'Kasur Single', 'Lemari', 'Meja Belajar'],
                'images_count' => 3,
            ],
            [
                'number' => '102',
                'title' => 'Garden View Standard 102',
                'type' => 'Standard',
                'price' => 500000,
                'status' => 'available',
                'description' => 'Ruangan standar yang tenang dengan jendela besar yang menghadap langsung ke taman hijau yang asri. Memberikan nuansa tropis yang menyegarkan setiap kali Anda membuka tirai di pagi hari.',
                'facilities' => ['AC', 'WiFi', 'Kasur Single', 'Lemari'],
                'images_count' => 2,
            ],
            [
                'number' => '103',
                'title' => 'Budget Standard 103',
                'type' => 'Standard',
                'price' => 500000,
                'status' => 'occupied',
                'description' => 'Pilihan praktis dan ekonomis bagi Anda yang mengutamakan fungsi tanpa mengabaikan kenyamanan dasar. Kamar ini tetap dilengkapi dengan standar kebersihan tinggi dan fasilitas WiFi.',
                'facilities' => ['WiFi', 'Kasur Single', 'Lemari', 'Meja Belajar'],
                'images_count' => 2,
            ],
            [
                'number' => '104',
                'title' => 'Access Standard 104',
                'type' => 'Standard',
                'price' => 500000,
                'status' => 'available',
                'description' => 'Kamar standar dengan keunggulan aksesibilitas terbaik, terletak di lantai dasar dan dekat dengan area parkir utama. Memudahkan Anda yang sering memiliki mobilitas luar ruangan tinggi.',
                'facilities' => ['AC', 'WiFi', 'Kasur Single', 'Lemari'],
                'images_count' => 2,
            ],
            [
                'number' => '105',
                'title' => 'Cozy Standard 105',
                'type' => 'Standard',
                'price' => 500000,
                'status' => 'maintenance',
                'description' => 'Kamar standar yang nyaman dengan pencahayaan yang hangat. Cocok untuk istirahat setelah seharian beraktivitas.',
                'facilities' => ['AC', 'WiFi', 'Kasur Single', 'Lemari'],
                'images_count' => 1,
            ],
            [
                'number' => '201',
                'title' => 'Executive Deluxe 201',
                'type' => 'Deluxe',
                'price' => 750000,
                'status' => 'occupied',
                'description' => 'Kamar Executive Deluxe yang menawarkan standar kemewahan dan privasi tingkat tinggi. Terletak di lantai 2, kamar ini dilengkapi dengan balkon pribadi.',
                'facilities' => ['AC', 'WiFi', 'Kasur Queen', 'Lemari', 'Meja Belajar', 'Kamar Mandi Dalam', 'Balkon', 'TV'],
                'images_count' => 4,
            ],
            [
                'number' => '202',
                'title' => 'Modern Deluxe 202',
                'type' => 'Deluxe',
                'price' => 750000,
                'status' => 'available',
                'description' => 'Menghadirkan konsep hunian modern minimalis yang fungsional namun tetap memberikan kesan luas dan lega. Interior didesain dengan sentuhan elegan.',
                'facilities' => ['AC', 'WiFi', 'Kasur Queen', 'Lemari', 'Meja Belajar', 'Kamar Mandi Dalam', 'TV'],
                'images_count' => 3,
            ],
            [
                'number' => '203',
                'title' => 'Bright Deluxe 203',
                'type' => 'Deluxe',
                'price' => 750000,
                'status' => 'occupied',
                'description' => 'Nikmati pagi yang cerah di Bright Deluxe 203 yang memiliki jendela kaca besar. Pencahayaan alami yang maksimal membuat ruangan terasa lebih luas.',
                'facilities' => ['AC', 'WiFi', 'Kasur Queen', 'Lemari', 'Meja Belajar', 'Kamar Mandi Dalam', 'Balkon'],
                'images_count' => 3,
            ],
            [
                'number' => '204',
                'title' => 'Silent Deluxe 204',
                'type' => 'Deluxe',
                'price' => 750000,
                'status' => 'available',
                'description' => 'Kamar deluxe yang tenang, jauh dari kebisingan. Sangat cocok bagi Anda yang membutuhkan konsentrasi tinggi untuk bekerja atau belajar.',
                'facilities' => ['AC', 'WiFi', 'Kasur Queen', 'Lemari', 'Meja Belajar', 'Kamar Mandi Dalam'],
                'images_count' => 2,
            ],
            [
                'number' => '205',
                'title' => 'Studio Deluxe 205',
                'type' => 'Deluxe',
                'price' => 750000,
                'status' => 'maintenance',
                'description' => 'Kamar deluxe dengan tata letak studio yang efisien. Memberikan kenyamanan maksimal dalam ruang yang fungsional.',
                'facilities' => ['AC', 'WiFi', 'Kasur Queen', 'Lemari', 'Meja Belajar', 'Kamar Mandi Dalam'],
                'images_count' => 2,
            ],
            [
                'number' => '301',
                'title' => 'Royal Suite 301',
                'type' => 'Suite',
                'price' => 1200000,
                'status' => 'available',
                'description' => 'Royal Suite adalah mahakarya hunian di Wisma Amal. Merupakan tipe kamar termewah yang luasnya setara dengan apartemen studio besar.',
                'facilities' => ['AC', 'WiFi', 'Kasur King', 'Lemari Besar', 'Meja Kerja', 'Kamar Mandi Dalam', 'Balkon', 'TV', 'Kulkas', 'Dapur Kecil', 'Sofa'],
                'images_count' => 5,
            ],
            [
                'number' => '302',
                'title' => 'Presidential Suite 302',
                'type' => 'Suite',
                'price' => 1200000,
                'status' => 'available',
                'description' => 'Kemewahan tanpa batas di Presidential Suite. Dilengkapi dengan fasilitas terbaik untuk pengalaman menginap yang tak terlupakan.',
                'facilities' => ['AC', 'WiFi', 'Kasur King', 'Lemari Besar', 'Meja Kerja', 'Kamar Mandi Dalam', 'Balkon', 'TV', 'Kulkas', 'Dapur Kecil', 'Sofa'],
                'images_count' => 5,
            ],
        ];

        $rooms = [];
        foreach ($roomsData as $roomData) {
            $imagesCount = $roomData['images_count'];
            unset($roomData['images_count']);

            $room = Room::create([
                'number' => $roomData['number'],
                'title' => $roomData['title'],
                'price' => $roomData['price'],
                'status' => $roomData['status'],
                'description' => $roomData['description'],
                'facilities' => $roomData['facilities'],
            ]);
            $rooms[] = $room;

            // Create dummy images for each room
            for ($i = 1; $i <= $imagesCount; $i++) {
                $imagePath = 'rooms/dummy-room-' . $room->number . '-' . $i . '.jpg';
                RoomImage::create([
                    'room_id' => $room->id,
                    'image_path' => $imagePath,
                    'order' => $i - 1,
                ]);

                // Generate physical file
                $fullpath = storage_path('app/public/' . $imagePath);
                $this->generatePlaceholder($fullpath, "Room " . $room->number, $room->status, 800, 600);
            }
        }

        // Create Leases
        // Active leases
        Lease::create([
            'user_id' => $users[0]->id,
            'room_id' => $rooms[0]->id,
            'start_date' => now()->subMonths(3),
            'end_date' => null,
            'price_per_month' => 500000,
            'total_price' => 1500000,
            'status' => 'active',
            'payment_proof' => 'payments/proof-1.jpg',
        ]);
        $this->generatePlaceholder(storage_path('app/public/payments/proof-1.jpg'), "Proof 1", "Payment", 400, 600);

        Lease::create([
            'user_id' => $users[1]->id,
            'room_id' => $rooms[5]->id,
            'start_date' => now()->subMonths(6),
            'end_date' => null,
            'price_per_month' => 750000,
            'total_price' => 4500000,
            'status' => 'active',
            'payment_proof' => 'payments/proof-2.jpg',
        ]);
        $this->generatePlaceholder(storage_path('app/public/payments/proof-2.jpg'), "Proof 2", "Payment", 400, 600);

        Lease::create([
            'user_id' => $users[2]->id,
            'room_id' => $rooms[2]->id,
            'start_date' => now()->subMonths(2),
            'end_date' => null,
            'price_per_month' => 500000,
            'total_price' => 1000000,
            'status' => 'active',
            'payment_proof' => 'payments/proof-3.jpg',
        ]);
        $this->generatePlaceholder(storage_path('app/public/payments/proof-3.jpg'), "Proof 3", "Payment", 400, 600);

        Lease::create([
            'user_id' => $users[3]->id,
            'room_id' => $rooms[7]->id,
            'start_date' => now()->subMonth(),
            'end_date' => null,
            'price_per_month' => 750000,
            'total_price' => 750000,
            'status' => 'active',
            'payment_proof' => 'payments/proof-4.jpg',
        ]);
        $this->generatePlaceholder(storage_path('app/public/payments/proof-4.jpg'), "Proof 4", "Payment", 400, 600);

        // Pending leases
        Lease::create([
            'user_id' => $users[5]->id,
            'room_id' => $rooms[6]->id,
            'start_date' => now()->addDays(7),
            'end_date' => null,
            'price_per_month' => 750000,
            'total_price' => 750000,
            'status' => 'pending',
            'payment_proof' => 'payments/proof-pending-1.jpg',
        ]);
        $this->generatePlaceholder(storage_path('app/public/payments/proof-pending-1.jpg'), "Pending 1", "Payment", 400, 600);

        Lease::create([
            'user_id' => $users[6]->id,
            'room_id' => $rooms[10]->id,
            'start_date' => now()->addDays(5),
            'end_date' => null,
            'price_per_month' => 1200000,
            'total_price' => 1200000,
            'status' => 'pending',
            'payment_proof' => 'payments/proof-pending-2.jpg',
        ]);
        $this->generatePlaceholder(storage_path('app/public/payments/proof-pending-2.jpg'), "Pending 2", "Payment", 400, 600);

        // Finished/ended leases (use cancelled status to match enum)
        Lease::create([
            'user_id' => $users[7]->id,
            'room_id' => $rooms[1]->id,
            'start_date' => now()->subYear(),
            'end_date' => now()->subMonths(2),
            'price_per_month' => 500000,
            'total_price' => 5000000,
            'status' => 'cancelled',
            'payment_proof' => 'payments/proof-old.jpg',
        ]);
        $this->generatePlaceholder(storage_path('app/public/payments/proof-old.jpg'), "Old Proof", "Payment", 400, 600);

        $this->command->info('Dummy data seeder completed successfully!');
        $this->command->info('Admin: admin@wismaamal.com / password');
        $this->command->info('Staff: staff@wismaamal.com / password');
        $this->command->info('Residents: ahmad@example.com (and others) / password');
    }

    private function generatePlaceholder($path, $text1, $text2, $width, $height)
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) return;

        $img = \imagecreatetruecolor($width, $height);
        $bgColor = \imagecolorallocate($img, 240, 240, 240);
        \imagefill($img, 0, 0, $bgColor);
        $textColor = \imagecolorallocate($img, 100, 100, 100);

        // Simple text without external fonts to ensure it always works
        \imagestring($img, 5, ($width / 2) - 50, ($height / 2) - 20, $text1, $textColor);
        \imagestring($img, 4, ($width / 2) - 40, ($height / 2) + 10, $text2, $textColor);

        if (function_exists('imagejpeg')) {
            \imagejpeg($img, $path, 80);
        } elseif (function_exists('imagepng')) {
            \imagepng($img, $path);
        } else {
            // Last resort: simple blank file if GD is totally broken
            \file_put_contents($path, "");
        }

        \imagedestroy($img);
    }
}
