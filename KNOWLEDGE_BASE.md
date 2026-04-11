# Knowledge Base: Backend Wisma Amal Gorontalo

Dokumen ini berisi analisis lengkap mengenai arsitektur, standar koding, dan mekanisme fitur pada proyek Backend Wisma Amal Gorontalo. Proyek ini menggunakan arsitektur **Modular Monolithic** yang memungkinkan setiap fitur (modul) diaktifkan atau dinonaktifkan secara independen.

---

## 1. Arsitektur Core

Proyek ini dibangun menggunakan **Laravel 11** dan paket `nwidart/laravel-modules`. Arsitektur ini membagi aplikasi ke dalam unit-unit fungsional yang disebut **Modules**.

### Struktur Direktori Utama
- `app/`: Berisi komponen global yang digunakan oleh semua modul (Traits, Providers core).
- `Modules/`: Tempat semua logika bisnis per fitur berada.
- `config/modules.php`: Konfigurasi utama sistem modularitas.
- `modules_statuses.json`: Daftar modul yang terinstal beserta status aktif/non-aktifnya.

---

## 2. Mekanisme Aktivasi Fitur

Setiap fitur dalam sistem ini direpresentasikan sebagai sebuah modul. Modul dapat dikelola melalui file `modules_statuses.json`.

```json
{
    "Room": true,
    "Resident": true,
    "Finance": true,
    "Rental": true,
    ...
}
```

- **True**: Modul aktif dan semua route, migrations, serta service provider-nya akan dimuat oleh Laravel.
- **False**: Modul dinonaktifkan. Sistem tidak akan mengenali route atau service dari modul tersebut.

---

## 3. Anatomi Sebuah Modul

Setiap modul di dalam `Modules/` memiliki struktur yang konsisten (mengikuti standar Repository-Service Pattern):

- **`Http/`**:
    - `Controllers/`: Entry point API. Hanya menangani request/response.
    - `Requests/`: Validasi data menggunakan FormRequest.
- **`Services/`**: Tempat logika bisnis utama berada. Menghubungkan controller dengan repository.
- **`Repositories/`**: Layer abstraksi database.
    - `Contracts/`: Berisi Interface untuk Repositories (mendukung *Dependency Inversion*).
    - `Eloquent/`: Implementasi konkret dari Repositories menggunakan Eloquent.
- **`Models/`**: Definisi tabel dan relasi Eloquent.
- **`Transformers/`**: Layer presentasi data menggunakan Laravel API Resources.
- **`Enums/`**: Definisi status atau tipe data konstan (misal: `LeaseStatus`).
- **`database/`**: Migrations, Seeders, dan Factories spesifik untuk modul tersebut.
- **`routes/api.php`**: Definisi endpoint API untuk modul tersebut.

---

## 4. Standar Koding & Pola (Patterns)

### 4.1 Repository-Service Pattern
Proyek ini mengadopsi pola ini untuk menjaga kebersihan kode dan mempermudah testing:
1.  **Controller** memanggil **Service**.
2.  **Service** menangani logika bisnis (perhitungan, validasi kompleks, koordinasi antar modul).
3.  **Service** memanggil **Repository** untuk operasi database.
4.  **Repository Interface** di-bind di Module Service Provider.

### 4.2 API Response yang Konsisten
Gunakan trait `App\Traits\ApiResponse` di Controller untuk output JSON yang seragam:
- `apiSuccess($data, $message, $code)`
- `apiError($message, $code, $errors)`

### 4.3 Komunikasi Antar-Modul
Modul sebaiknya tidak mengakses database modul lain secara langsung. Komunikasi dilakukan melalui Service atau Interface yang disediakan oleh modul lain.
*Contoh:* `RentalService` memanggil `FinanceService` untuk membuat invoice saat ada penyewaan baru.

---

## 5. Database & Migrasi

### Pemisahan Tanggung Jawab
- **Global Migrations**: Berada di `database/migrations/` (User, Permissions, Personal Access Tokens).
- **Module Migrations**: Berada di `Modules/[ModuleName]/database/migrations/`. 

---

## 6. Integrasi Eksternal

### 6.1 RBAC (Spatie Permission)
Manajemen akses berbasis Role dan Permission. Data persistensi ada di tabel global, namun pengecekan dilakukan di level middleware atau policy masing-masing modul.

### 6.2 Midtrans Payment Gateway
Digunakan untuk menangani pembayaran sewa. Logikanya terintegrasi di dalam `Modules/Finance`.

### 6.3 Scramble (Auto API Docs)
API didokumentasikan secara otomatis. Anda dapat mengaksesnya di endpoint `/docs/api` (jika diaktifkan). Dokumentasi ini membaca *type-hint* dan *doc-blocks* dari Controller dan Model.

### 6.4 Pelaporan Kerusakan (Maintenance Module)
Fitur sentralisasi pelaporan kerusakan untuk `Resident` yang berada sepenuhnya di modul `Maintenance`. 
Logika kuncinya:
- *Resident* dapat melaporkan kerusakan dengan multi-foto. 
- *Admin* / *Super-Admin* dapat merespons ("timeline reply") termasuk menambah foto dan merubah status laporan.
- Laporan bebas (`nullable`) dari ikatan kamar jika kerusakan ada di fasilitas umum.

**Data Seeding**:
Modul ini menyertakan `MaintenanceRequestSeeder` yang menghasilkan data dummy untuk:
- Laporan baru (*Pending*).
- Laporan yang sedang ditangani (*In Progress*) dengan update dari admin.
- Laporan yang sudah selesai (*Completed*) dengan riwayat progres lengkap.
- Laporan yang dibatalkan (*Cancelled*).

> [!NOTE]
> Selalu gunakan `Modules\Auth\Models\User` daripada `App\Models\User` (default Laravel) di dalam modul untuk menjaga konsistensi dengan sistem Auth modular.

### 6.5 Manajemen Inventaris (Inventory Module)
Modul ini menangani pencatatan aset barang di Wisma. 
Logika kuncinya:
- **Otomatisasi Finansial**: Setiap penambahan barang dengan `purchase_price` akan otomatis memanggil `FinanceService@recordExpense` untuk mencatat pengeluaran di laporan keuangan.
- **Sinkronisasi Revisi**: Update pada harga beli barang akan melakukan *sync* terhadap data pengeluaran terkait di modul Finance menggunakan mekanisme `ByReference`.
- **Enum-based Condition**: Menggunakan Enum `ItemCondition` (good, fair, broken, lost) untuk validasi status barang yang konsisten antara Database dan UI.

---

## 7. Workflow Pengembangan Fitur Baru

Jika ingin menambahkan fitur baru:
1.  Buat modul baru: `php artisan module:make [FeatureName]`.
2.  Definisikan migrasi di dalam modul.
3.  Implementasikan Repository Interface dan Eloquent Repository.
4.  Buat Service untuk menampung logika bisnis.
5.  Buat Controller dan sambungkan ke route API.
6.  Daftarkan/Bind Interface ke Implementasinya di `Providers/[ModuleName]ServiceProvider.php`.

---
## 8. Strategi Dokumen Hidup (Living Document Strategy)

Dokumen ini bersifat dinamis dan akan diperbarui setiap kali:
-   **Fitur Baru Ditambahkan**: Saat modul baru dibuat, struktur dan tujuannya akan dicatat di sini.
-   **Perubahan Arsitektur**: Jika ada perubahan pada cara modul berinteraksi atau perubahan pada core aplikasi.
-   **Keputusan Teknis Penting**: Dokumentasi mengenai integrasi pihak ketiga baru atau perubahan standar koding.

> [!IMPORTANT]
> Konsistensi antara kode dan dokumentasi adalah prioritas utama. Setiap sesi pengembangan harus diakhiri dengan verifikasi dan pembaruan pada `KNOWLEDGE_BASE.md`.
