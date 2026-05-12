<?php

namespace Modules\Inventory\database\seeders;

use Illuminate\Database\Seeder;

class InventoryDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Kasur Single King Koil', 'description' => 'Kasur berkualitas tinggi untuk kamar standard', 'quantity' => 10, 'condition' => 'good', 'purchase_price' => 2500000],
            ['name' => 'AC Split 1/2 PK Sharp', 'description' => 'AC hemat energi', 'quantity' => 15, 'condition' => 'good', 'purchase_price' => 3200000],
            ['name' => 'Lemari Pakaian Kayu Jati', 'description' => 'Lemari 2 pintu minimalis', 'quantity' => 20, 'condition' => 'good', 'purchase_price' => 1500000],
            ['name' => 'Meja Belajar Minimalis', 'description' => 'Meja kayu dengan laci', 'quantity' => 20, 'condition' => 'good', 'purchase_price' => 450000],
            ['name' => 'Kursi Kerja Ergonomis', 'description' => 'Kursi dengan sandaran punggung', 'quantity' => 20, 'condition' => 'good', 'purchase_price' => 650000],
            ['name' => 'Bantal Silicon Microfiber', 'description' => 'Bantal empuk standar hotel', 'quantity' => 40, 'condition' => 'good', 'purchase_price' => 85000],
            ['name' => 'Guling Silicon Microfiber', 'description' => 'Guling empuk standar hotel', 'quantity' => 40, 'condition' => 'good', 'purchase_price' => 90000],
            ['name' => 'TV LED 32 Inch Samsung', 'description' => 'Smart TV untuk kamar suite', 'quantity' => 5, 'condition' => 'good', 'purchase_price' => 2100000],
            ['name' => 'Kulkas Mini Portable', 'description' => 'Kulkas kecil untuk kamar suite', 'quantity' => 5, 'condition' => 'good', 'purchase_price' => 1200000],
            ['name' => 'Dispenser Air Sharp', 'description' => 'Dispenser di area umum', 'quantity' => 2, 'condition' => 'fair', 'purchase_price' => 1800000],
            ['name' => 'Sofa 2 Seater Grey', 'description' => 'Sofa untuk area ruang tamu suite', 'quantity' => 5, 'condition' => 'good', 'purchase_price' => 2800000],
            ['name' => 'Mesin Cuci LG Front Load', 'description' => 'Fasilitas cuci bersama', 'quantity' => 2, 'condition' => 'good', 'purchase_price' => 4500000],
            ['name' => 'Cermin Dinding Besar', 'description' => 'Cermin hias di kamar', 'quantity' => 20, 'condition' => 'good', 'purchase_price' => 150000],
            ['name' => 'Lampu LED Phillips 9W', 'description' => 'Stok lampu cadangan', 'quantity' => 50, 'condition' => 'good', 'purchase_price' => 35000],
            ['name' => 'Sprei Set Katun Putih', 'description' => 'Satu set sprei dan sarung bantal', 'quantity' => 60, 'condition' => 'good', 'purchase_price' => 120000],
        ];

        foreach ($items as $item) {
            \Modules\Inventory\Models\Inventory::create($item);
        }
    }
}
