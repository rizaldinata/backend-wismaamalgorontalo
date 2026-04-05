# Panduan Membuat Data Dummy Room

Dokumentasi ini menjelaskan cara membuat data dummy untuk modul Room yang sudah diperbarui (dengan facilities dan images).

## 📋 Isi Data Dummy

Seeder akan membuat **8 kamar** dengan variasi:
- **3 kamar Standard** (Rp 500.000) - nomor 101, 102, 103, 104
- **3 kamar Deluxe** (Rp 750.000) - nomor 201, 202, 203
- **1 kamar Suite** (Rp 1.200.000) - nomor 301

Setiap kamar memiliki:
- ✅ Facilities (list fasilitas)
- ✅ Multiple images (2-5 foto per kamar)
- ✅ Status berbeda (available, occupied, maintenance)

## 🚀 Cara Menjalankan Seeder

### Opsi 1: Jalankan Seeder Room Saja

```bash
php artisan db:seed --class=Modules\\Room\\database\\seeders\\RoomDatabaseSeeder
```

### Opsi 2: Tambahkan ke DatabaseSeeder Utama

Edit file `database/seeders/DatabaseSeeder.php`, tambahkan:

```php
public function run(): void
{
    $this->call([
        \Modules\Room\database\seeders\RoomDatabaseSeeder::class,
        // seeder lainnya...
    ]);
}
```

Lalu jalankan:
```bash
php artisan db:seed
```

### Opsi 3: Fresh Migration + Seed

Untuk reset database dan isi ulang dengan data dummy:

```bash
php artisan migrate:fresh --seed
```

⚠️ **PERHATIAN**: Perintah ini akan **menghapus semua data** di database!

## 🖼️ Tentang Images

Seeder akan membuat record images dengan path placeholder:
- `rooms/dummy-room-101-1.jpg`
- `rooms/dummy-room-101-2.jpg`
- dst...

**File gambar belum ada secara fisik**, hanya record di database.

### Cara 1: Generate Placeholder Images Otomatis

Jalankan seeder tambahan untuk generate gambar placeholder:

```bash
php artisan db:seed --class=Modules\\Room\\database\\seeders\\RoomImagePlaceholderSeeder
```

Ini akan membuat file gambar placeholder sederhana dengan GD Library (sudah built-in di PHP).

### Cara 2: Upload Gambar Manual via API

Gunakan endpoint upload untuk menambahkan gambar asli:

```bash
POST /api/rooms/{id}/images
Content-Type: multipart/form-data
Authorization: Bearer {token}

Body:
- images[]: [file gambar 1]
- images[]: [file gambar 2]
```

### Cara 3: Copy Gambar Manual ke Storage

1. Siapkan gambar-gambar kamar
2. Copy ke folder: `storage/app/public/rooms/`
3. Rename sesuai dengan nama di database, contoh:
   - `dummy-room-101-1.jpg`
   - `dummy-room-101-2.jpg`
   - dst...

## 🔄 Reset Data Dummy

Jika ingin hapus dan buat ulang data dummy:

```bash
# Hapus semua data room (dan images akan ikut terhapus karena cascade)
php artisan tinker
>>> \Modules\Room\Models\Room::truncate();
>>> exit

# Jalankan seeder lagi
php artisan db:seed --class=Modules\\Room\\database\\seeders\\RoomDatabaseSeeder
```

## 📊 Contoh Data yang Dihasilkan

### Kamar Standard (101)
```json
{
  "number": "101",
  "type": "Standard",
  "price": 500000,
  "status": "available",
  "description": "Kamar standar dengan fasilitas lengkap, nyaman untuk penghuni jangka panjang",
  "facilities": ["AC", "WiFi", "Kasur Single", "Lemari", "Meja Belajar"],
  "images": [
    {"id": 1, "url": "http://localhost/storage/rooms/dummy-room-101-1.jpg", "order": 0},
    {"id": 2, "url": "http://localhost/storage/rooms/dummy-room-101-2.jpg", "order": 1},
    {"id": 3, "url": "http://localhost/storage/rooms/dummy-room-101-3.jpg", "order": 2}
  ]
}
```

### Kamar Suite (301)
```json
{
  "number": "301",
  "type": "Suite",
  "price": 1200000,
  "status": "available",
  "description": "Kamar suite mewah dengan ruang tamu terpisah dan dapur kecil",
  "facilities": [
    "AC", "WiFi", "Kasur King", "Lemari Besar", "Meja Kerja",
    "Kamar Mandi Dalam", "Balkon", "TV", "Kulkas", "Dapur Kecil", "Sofa"
  ],
  "images": [
    {"id": 21, "url": "http://localhost/storage/rooms/dummy-room-301-1.jpg", "order": 0},
    {"id": 22, "url": "http://localhost/storage/rooms/dummy-room-301-2.jpg", "order": 1},
    {"id": 23, "url": "http://localhost/storage/rooms/dummy-room-301-3.jpg", "order": 2},
    {"id": 24, "url": "http://localhost/storage/rooms/dummy-room-301-4.jpg", "order": 3},
    {"id": 25, "url": "http://localhost/storage/rooms/dummy-room-301-5.jpg", "order": 4}
  ]
}
```

## ✅ Verifikasi Data

Setelah seeding, cek data via API:

```bash
GET http://localhost/api/rooms
```

Atau via tinker:
```bash
php artisan tinker
>>> \Modules\Room\Models\Room::with('images')->get();
```

## 🎯 Tips

1. **Untuk Development**: Gunakan placeholder images (lebih cepat)
2. **Untuk Demo/Presentasi**: Upload gambar asli kamar yang menarik
3. **Untuk Testing**: Data dummy sudah cukup untuk test API

---

**File Seeder:**
- `Modules/Room/database/seeders/RoomDatabaseSeeder.php` - Seeder utama
- `Modules/Room/database/seeders/RoomImagePlaceholderSeeder.php` - Generate placeholder images (opsional)
