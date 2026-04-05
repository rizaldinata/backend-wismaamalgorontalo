<?php

namespace Modules\Room\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Room\Models\Room;
use Modules\Room\Models\RoomImage;

class RoomDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            [
                'number' => '101',
                'title' => 'Standard Deluxe 101',
                'price' => 500000,
                'status' => 'available',
                'description' => 'Kamar Standard Deluxe yang dirancang khusus untuk kenyamanan maksimal Anda. Menawarkan suasana tenang dengan pencahayaan alami yang cukup. Dilengkapi dengan fasilitas modern, area kerja yang ergonomis, dan koneksi internet cepat, menjadikannya pilihan ideal baik bagi wisatawan maupun profesional yang membutuhkan hunian berkualitas tinggi di pusat kota. Nikmati istirahat yang berkualitas di atas kasur premium kami yang menjamin kesegaran di pagi hari.',
                'facilities' => ['AC', 'WiFi', 'Kasur Single', 'Lemari', 'Meja Belajar'],
                'images_count' => 3,
            ],
            [
                'number' => '102',
                'title' => 'Garden View Standard 102',
                'price' => 500000,
                'status' => 'available',
                'description' => 'Ruangan standar yang tenang dengan jendela besar yang menghadap langsung ke taman hijau yang asri. Memberikan nuansa tropis yang menyegarkan setiap kali Anda membuka tirai di pagi hari. Kamar ini sangat cocok bagi Anda yang mencari ketenangan dari hiruk-pikuk aktivitas sehari-hari namun tetap menginginkan aksesibilitas dan kenyamanan hunian modern. Dilengkapi dengan perabotan kayu berkualitas dan atmosfer yang hangat.',
                'facilities' => ['AC', 'WiFi', 'Kasur Single', 'Lemari'],
                'images_count' => 2,
            ],
            [
                'number' => '201',
                'title' => 'Executive Deluxe 201',
                'price' => 750000,
                'status' => 'occupied',
                'description' => 'Kamar Executive Deluxe yang menawarkan standar kemewahan dan privasi tingkat tinggi. Terletak di lantai yang lebih tinggi, kamar ini dilengkapi dengan balkon pribadi yang luas, tempat sempurna untuk menikmati secangkir kopi sambil melihat pemandangan kota. Interior didesain dengan sentuhan elegan, serta memiliki kamar mandi dalam yang mewah dengan perlengkapan sanitasi premium. Pilihan utama bagi mereka yang tidak ingin berkompromi dengan kualitas.',
                'facilities' => ['AC', 'WiFi', 'Kasur Queen', 'Lemari', 'Meja Belajar', 'Kamar Mandi Dalam', 'Balkon', 'TV'],
                'images_count' => 4,
            ],
            [
                'number' => '202',
                'title' => 'Modern Deluxe 202',
                'price' => 750000,
                'status' => 'available',
                'description' => 'Menghadirkan konsep hunian modern minimalis yang fungsional namun tetap memberikan kesan luas dan lega. Kamar Deluxe ini menonjolkan estetika interior terkini dengan perpaduan warna netral yang menenangkan mata. Area penyimpanan yang dirancang dengan cerdas memastikan ruangan tetap rapi, sementara fasilitas hiburan TV layar datar dan meja belajar minimalis menambah nilai kenyamanan tinggal Anda.',
                'facilities' => ['AC', 'WiFi', 'Kasur Queen', 'Lemari', 'Meja Belajar', 'Kamar Mandi Dalam', 'TV'],
                'images_count' => 3,
            ],
            [
                'number' => '301',
                'title' => 'Royal Suite 301',
                'price' => 1200000,
                'status' => 'available',
                'description' => 'Royal Suite adalah mahakarya hunian di Wisma Amal. Merupakan tipe kamar termewah yang luasnya setara dengan apartemen studio besar. Memiliki area ruang tamu terpisah yang dilengkapi dengan sofa premium untuk menerima tamu, serta dapur kecil (kitchenette) yang lengkap bagi Anda yang gemar menyiapkan hidangan sendiri. Kamar mandi dirancang layaknya spa pribadi, memberikan pengalaman relaksasi total setelah seharian beraktivitas.',
                'facilities' => ['AC', 'WiFi', 'Kasur King', 'Lemari Besar', 'Meja Kerja', 'Kamar Mandi Dalam', 'Balkon', 'TV', 'Kulkas', 'Dapur Kecil', 'Sofa'],
                'images_count' => 5,
            ],
            [
                'number' => '103',
                'title' => 'Budget Standard 103',
                'price' => 500000,
                'status' => 'maintenance',
                'description' => 'Pilihan praktis dan ekonomis bagi Anda yang mengutamakan fungsi tanpa mengabaikan kenyamanan dasar. Kamar ini tetap dilengkapi dengan standar kebersihan tinggi dan fasilitas WiFi untuk mendukung aktivitas digital Anda. Meskipun saat ini dalam status pemeliharaan berkala untuk peningkatan kualitas AC, kamar ini tetap menjadi favorit tamu jangka panjang yang mencari efisiensi biaya hunian.',
                'facilities' => ['WiFi', 'Kasur Single', 'Lemari', 'Meja Belajar'],
                'images_count' => 2,
            ],
            [
                'number' => '104',
                'title' => 'Access Standard 104',
                'price' => 500000,
                'status' => 'available',
                'description' => 'Kamar standar dengan keunggulan aksesibilitas terbaik, terletak di lantai dasar dan dekat dengan area parkir utama. Memudahkan Anda yang sering memiliki mobilitas luar ruangan tinggi. Walaupun berada dekat dengan akses umum, desain kedap suara kami memastikan Anda tetap bisa beristirahat dengan tenang tanpa gangguan kebisingan dari luar.',
                'facilities' => ['AC', 'WiFi', 'Kasur Single', 'Lemari'],
                'images_count' => 2,
            ],
            [
                'number' => '203',
                'title' => 'Bright Deluxe 203',
                'price' => 750000,
                'status' => 'occupied',
                'description' => 'Nikmati pagi yang cerah di Bright Deluxe 203 yang memiliki jendela kaca besar dari langit-langit hingga lantai. Pencahayaan alami yang maksimal tidak hanya membuat ruangan terasa lebih luas tetapi juga memberikan energi positif bagi penghuninya. Dilengkapi dengan balkon pribadi untuk menikmati udara segar Gorontalo, menjadikan setiap momen di kamar ini terasa istimewa.',
                'facilities' => ['AC', 'WiFi', 'Kasur Queen', 'Lemari', 'Meja Belajar', 'Kamar Mandi Dalam', 'Balkon'],
                'images_count' => 3,
            ],
        ];

        foreach ($rooms as $roomData) {
            $imagesCount = $roomData['images_count'];
            unset($roomData['images_count']);

            $room = Room::create($roomData);

            for ($i = 0; $i < $imagesCount; $i++) {
                RoomImage::create([
                    'room_id' => $room->id,
                    'image_path' => 'rooms/dummy-room-' . $room->number . '-' . ($i + 1) . '.jpg',
                    'order' => $i,
                ]);
            }
        }

        $this->command->info('Berhasil membuat ' . count($rooms) . ' data room dengan facilities dan images!');
    }
}
