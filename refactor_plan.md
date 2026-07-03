# Roadmap Refactor: GET Data Antar Modul (Direct Service Access)

> Dokumen ini adalah panduan dan progress tracker untuk memperbaiki pola akses **baca data (GET)** lintas modul di backend Wisma Amal Gorontalo.
> Update status task dengan mengganti `[ ]` menjadi `[x]` ketika selesai.

---

## Konteks & Perbedaan dengan ROADMAP_REFACTOR.md

`ROADMAP_REFACTOR.md` (sudah selesai, 11 fase) membenahi sisi **WRITE** (ADD/UPDATE/DELETE) dengan pola Event-Driven, sesuai baris terakhir tabel "Aturan Emas":

> **Feature → Feature (WRITE)** → wajib Event-Driven ✅ *(sudah selesai)*

Roadmap ini membenahi baris yang **belum** dikerjakan:

> **Feature → Feature (GET)** → saat ini masih *Direct Call* **tanpa pengecekan status modul** ⚠️ *(bug arsitektur — fokus dokumen ini)*

Kondisi sekarang: modul Feature saling mengimpor Eloquent Model modul lain langsung (contoh: `Invoice::with('schedule')` di Finance memanggil Model milik Schedule). Kalau modul tujuan dimatikan di `modules_statuses.json`, endpoint GET yang menyentuh relasi itu akan **error/crash**, bukan sekadar kehilangan data — ini melanggar prinsip "modul boleh dimatikan tanpa merusak modul lain" yang sudah dibangun susah payah di roadmap sebelumnya.

Target akhir: pola **Direct Service Access** — Service class biasa (bukan Eloquent Model, bukan Interface/Container binding) + pengecekan `Module::isEnabled()` sebelum memanggil. Lihat dokumen "Arsitektur Komunikasi Langsung (Versi Sangat Sederhana)" untuk contoh kode lengkap.

---

## Cara Menggunakan Dokumen Ini

### Persiapan Sekali Saja (Sebelum Mulai Pertama Kali)

Ucapkan ke Claude Code, satu per satu:

```
"Baca ROADMAP_REFACTOR_GET_DATA.md, ini lanjutan dari ROADMAP_REFACTOR.md tapi fokus ke GET data. Kita mulai dari Fase 0."
```

### Setiap Kali Mulai Sesi Refactor

```
"Lanjut refactor GET data. Baca ROADMAP_REFACTOR_GET_DATA.md dan lanjutkan dari task yang belum selesai."
```

### Setelah Satu Task/Sub-fase Selesai

**1. Update checkbox:**
```
"Task [nomor] sudah selesai, update checkboxnya di ROADMAP_REFACTOR_GET_DATA.md"
```

**2. Kalau itu sub-fase Eksplorasi, minta hasilnya dicatat:**
```
"Isi tabel Lampiran Peta Ketergantungan GET di dokumen ini dengan hasil eksplorasi task [nomor]"
```

**3. Commit:**
```
"Commit semua perubahan termasuk ROADMAP_REFACTOR_GET_DATA.md dengan pesan: refactor(get): selesai task [nomor] - [nama task]"
```

**4a. Lanjut:**
```
"Lanjut ke task berikutnya"
```

**4b. Berhenti dulu:**
```
"Saya mau berhenti dulu, pastikan semua sudah tersimpan dan tidak ada kode yang rusak"
```

### Jika Ada Error di Tengah Task

```
"Ada error, task [nomor] belum selesai. Tolong bantu perbaiki tanpa melanjutkan ke task berikutnya"
```

---

## Pola Target (Ringkasan)

**Sisi Provider** (modul yang datanya dibaca) — Service class statis, return array/DTO, **bukan** Eloquent Model:

```php
// Modules/Schedule/Services/ScheduleService.php
namespace Modules\Schedule\Services;

class ScheduleService {
    public static function getById(int $scheduleId): ?array {
        $data = \DB::table('room_schedules')->where('id', $scheduleId)->first();
        return $data ? (array) $data : null;
    }
}
```

**Sisi Consumer** (modul yang minta data) — cek status modul dulu via helper `ModuleGate`, baru panggil:

```php
// Modules/Finance/Http/Controllers/InvoiceController.php
use Modules\Schedule\Services\ScheduleService;
use App\Support\ModuleGate;

$dataJadwal = ModuleGate::isActive('Schedule')
    ? ScheduleService::getById($scheduleId)
    : null;
```

Tidak ada `belongsTo`/`hasMany` lintas modul, tidak ada Interface/Container binding — cukup class biasa + satu pengecekan status.

---

## Aturan Wajib Selama Refactor

1. **Satu modul = satu branch** — jangan gabung perubahan dari dua modul berbeda
2. **API tidak boleh berubah** — response endpoint yang dipakai Flutter harus tetap sama persis, baik saat modul tetangga aktif **maupun nonaktif**
3. **Tidak ada Eloquent relationship lintas modul** (`belongsTo`, `hasMany`, `hasOne`, `belongsToMany` yang menunjuk ke `Modules\X\Models\...` milik modul lain) — ganti dengan static Service
4. **Setiap akses ke modul Feature lain wajib didahului `ModuleGate::isActive()`** — kecuali ke modul Core (Auth, Room, Setting) yang selalu aman diakses langsung, tidak perlu diguard
5. **Sepakati kontrak fallback dulu sebelum refactor** — apa yang dikembalikan kalau modul tetangga mati? (`null`, array kosong, atau pesan tertentu) — catat di Lampiran, jangan diputuskan dadakan saat coding
6. **Provider harus siap sebelum consumer-nya dikerjakan** — jangan refactor Finance membaca Schedule sebelum `ScheduleService` selesai di Fase 2
7. **Jalankan `php artisan test` setelah setiap sub-fase**
8. **Test dua kondisi** untuk tiap relasi yang direfactor: modul tetangga AKTIF dan modul tetangga NONAKTIF
9. **Selalu test di staging sebelum merge ke main**

---

## Klasifikasi Modul (Recap)

| Kategori | Modul | Terlibat di Roadmap Ini? |
|---|---|:---:|
| **Core** | Auth, Room, Setting | ❌ Tidak — akses langsung ke Core sudah aman, tidak perlu guard |
| **Feature** | Notification, Schedule, Inventory, Guest, Maintenance, Finance, Dashboard* | ✅ Ya |

*\*Dashboard perlu dikonfirmasi keberadaannya sebagai modul terpisah di Fase 0 — kalau ternyata cuma endpoint `/api/finance/dashboard/*` di dalam modul Finance (bukan modul sendiri), Fase 7 di-skip dan ditandai N/A.*

**Kenapa urutannya begini:**
`Notification` diaudit duluan karena kemungkinan besar tidak baca modul lain langsung (dia reaktif terhadap event, datanya sudah dibawa payload event). `Schedule` dikerjakan kedua karena dia provider paling sentral — begitu `ScheduleService` jadi, `Inventory`, `Guest`, `Maintenance`, dan `Finance` tinggal konsumsi, tidak perlu bikin dari nol tiap fase. `Finance` sengaja diletakkan mendekati akhir karena paling kompleks (dia provider untuk Guest/Dashboard, sekaligus consumer Schedule). `Dashboard` paling akhir karena dia agregator dari hampir semua modul lain.

---

## Progress Keseluruhan

| Fase | Modul | Status |
|---|---|---|
| 0 | Persiapan & Tooling | ✅ Selesai |
| 1 | Notification (Audit) | ✅ Selesai |
| 2 | Schedule (Provider Utama) | ⬜ Belum |
| 3 | Inventory | ⬜ Belum |
| 4 | Guest | ⬜ Belum |
| 5 | Maintenance | ⬜ Belum |
| 6 | Finance | ⬜ Belum |
| 7 | Dashboard | ⬜ Belum |
| 8 | Cleanup & Verifikasi Final | ⬜ Belum |

---

## Fase 0: Persiapan & Tooling

> Tujuan: siapkan helper yang dipakai berulang di semua fase, dan petakan kondisi awal sebelum menyentuh kode apapun.

- [x] **0.1** Buat branch `refactor/get-phase-0-persiapan` dari `staging`
- [x] **0.2** Jalankan `php artisan test` — catat baseline (jumlah passed & assertions)
- [x] **0.3** Buat `App\Support\ModuleGate` dengan method `isActive(string $moduleName): bool` yang membungkus `Module::has() && Module::isEnabled()` — supaya tidak menulis ulang dua kondisi ini di puluhan tempat
- [x] **0.4** Buat `tests/Unit/Support/ModuleGateTest.php` — 3 skenario: modul aktif → true, modul dimatikan → false, nama modul tidak dikenal → false
- [x] **0.5** Audit awal — grep seluruh codebase untuk Eloquent relationship yang menunjuk namespace `Modules\` berbeda dari pemiliknya (`belongsTo`, `hasMany`, `hasOne`, `belongsToMany`). Simpan hasil mentahnya (belum perlu rapi) untuk diisi ke Lampiran per modul nanti
- [x] **0.6** Audit awal — grep semua `use Modules\` di tiap Controller/Service untuk melihat model/service apa saja yang diimpor lintas modul, dan modul mana yang sebenarnya belum punya Dashboard sebagai modul terpisah (konfirmasi untuk Fase 7)
- [x] **0.7** Jalankan `php artisan test` — pastikan masih hijau
- [x] **0.8** Merge ke `staging`

---

## Fase 1: Modul Notification (Audit Only)

> Tujuan: pastikan Notification tidak diam-diam membaca modul lain secara langsung. Kemungkinan besar hasil audit N/A karena sifatnya reaktif terhadap event (data sudah dibawa payload).
> Branch: `refactor/get-phase-1-notification`

### Sub-fase A: Eksplorasi

- [x] **1.A.1** Buat branch `refactor/get-phase-1-notification` dari `staging`
- [x] **1.A.2** Cari semua `use Modules\` di dalam `Modules/Notification` — modul apa saja yang diimpor?
- [x] **1.A.3** Cari apakah ada Controller/Service di modul lain yang mengimpor Model milik Notification (misal untuk baca log notifikasi langsung)
- [x] **1.A.4** Catat hasil di Lampiran (kalau kosong, tulis "N/A — tidak ditemukan")

### Sub-fase B: Refactor

- [x] **1.B.1** Jika 1.A menemukan relasi: buat Service statis di sisi provider + guard `ModuleGate` di sisi consumer, mengikuti Pola Target
- [x] **1.B.2** Jika 1.A kosong: skip, tidak ada yang perlu direfactor
- [x] **1.B.3** Jalankan `php artisan test`
- [x] **1.B.4** Merge ke `staging`

---

## Fase 2: Modul Schedule (Provider Utama)

> Tujuan: bangun `ScheduleService` sebagai satu-satunya pintu masuk resmi ke data jadwal untuk modul lain. Fase ini paling krusial karena semua fase berikutnya bergantung padanya.
> Branch: `refactor/get-phase-2-schedule`

### Sub-fase A: Eksplorasi

- [x] **2.A.1** Buat branch `refactor/get-phase-2-schedule` dari `staging`
- [x] **2.A.2** Cari semua tempat di modul lain yang mengimpor `Modules\Schedule\Models\Schedule` langsung (Eloquent relationship atau query manual) — ini daftar konsumen yang perlu dilayani
- [x] **2.A.3** Untuk tiap konsumen, catat: data apa saja yang diambil (kolom/field), dipanggil dari mana (Controller/Service), dan dipakai untuk apa
- [x] **2.A.4** Cek apakah Schedule sendiri membaca modul Feature lain (harusnya tidak — kalau ada, catat juga)
- [x] **2.A.5** Sepakati kontrak fallback: kalau Schedule mati, tiap konsumen di atas seharusnya menerima apa? (biasanya `null`)
- [x] **2.A.6** Isi Lampiran dengan hasil di atas

### Sub-fase B: Refactor

- [x] **2.B.1** Buat `Modules\Schedule\Services\ScheduleService` dengan method publik statis sesuai kebutuhan nyata dari 2.A.3 (contoh kandidat: `getById`, `getActiveByRoom`, `getByTenantUserId` — sesuaikan dengan temuan, jangan bikin method yang tidak dipakai)
- [x] **2.B.2** Pastikan tiap method mengembalikan `array`/`?array`, **bukan** Eloquent Model — supaya modul lain tidak bisa iseng chaining relationship lagi
- [x] **2.B.3** Buat `tests/Unit/Modules/Schedule/ScheduleServiceTest.php` — verifikasi tiap method dengan data ada & data tidak ada
- [x] **2.B.4** Jalankan `php artisan test`
- [ ] **2.B.5** Merge ke `staging`

> **Catatan:** Fase ini belum mengubah kode di modul consumer (Finance, Guest, Maintenance) — itu dikerjakan di fase masing-masing modul, sekarang `ScheduleService` sudah tersedia untuk dipakai.

---

## Fase 3: Modul Inventory
> Branch: `refactor/get-phase-3-inventory`

### Sub-fase A: Eksplorasi
- [x] **3.A.1** Buat branch `refactor/get-phase-3-inventory` dari `staging`
- [x] **3.A.2** Cari `use Modules\` di dalam `Modules/Inventory` — apakah ada Model/relationship lintas modul untuk GET?
- [x] **3.A.3** Cari apakah ada modul lain yang membaca data Inventory langsung via Eloquent (misal Dashboard nanti)
- [x] **3.A.4** Isi Lampiran

### Sub-fase B: Refactor
- [x] **3.B.1** Jika ditemukan konsumsi ke modul lain: ganti dengan Service + `ModuleGate` (pakai `ScheduleService` dari Fase 2 kalau relevan)
- [x] **3.B.2** Jika Inventory adalah provider bagi modul lain: buat `Modules\Inventory\Services\InventoryService` dengan method baca yang dibutuhkan
- [x] **3.B.3** Test kondisi modul tetangga aktif & nonaktif
- [x] **3.B.4** Jalankan `php artisan test`
- [x] **3.B.5** Merge ke `staging`

---

## Fase 4: Modul Guest

> Guest sudah punya `GuestActiveContext` dengan `schedule_reference_id` dari Fase 4 roadmap sebelumnya — kemungkinan besar ada pemanggilan Schedule/Finance langsung untuk data tambahan (nama penyewa, harga kamar, dst) yang perlu digeser ke pola baru.
> Branch: `refactor/get-phase-4-guest`

### Sub-fase A: Eksplorasi
- [x] **4.A.1** Buat branch `refactor/get-phase-4-guest` dari `staging`
- [x] **4.A.2** Cari `use Modules\Schedule\` dan `use Modules\Finance\` di dalam `Modules/Guest`
- [x] **4.A.3** Untuk tiap pemakaian, catat: dipanggil dari mana, data apa yang diambil
- [x] **4.A.4** Cek apakah ada modul lain yang membaca data Guest langsung (misal Dashboard)
- [x] **4.A.5** Isi Lampiran

### Sub-fase B: Refactor
- [x] **4.B.1** Ganti pemanggilan Schedule langsung dengan `ScheduleService::...` + `ModuleGate::isActive('Schedule')`
- [x] **4.B.2** Jika ada pemanggilan Finance langsung: tunda dulu sampai Fase 6 (`FinanceService`) selesai, atau kerjakan sekarang kalau Finance ternyata tidak butuh Guest balik (cek arah ketergantungan supaya tidak sirkuler)
- [x] **4.B.3** Jika Guest adalah provider bagi modul lain: buat `Modules\Guest\Services\GuestService` (read-only methods)
- [x] **4.B.4** Test kondisi Schedule aktif & nonaktif untuk endpoint Guest yang relevan
- [x] **4.B.5** Jalankan `php artisan test`
- [ ] **4.B.6** Merge ke `staging`

---

## Fase 5: Modul Maintenance

> Branch: `refactor/get-phase-5-maintenance`

### Sub-fase A: Eksplorasi
- [x] **5.A.1** Buat branch `refactor/get-phase-5-maintenance` dari `staging`
- [x] **5.A.2** Cari `use Modules\Schedule\` di dalam `Modules/Maintenance` (kemungkinan damage report terhubung ke jadwal/kamar tertentu)
- [x] **5.A.3** Cek juga apakah masih ada sisa referensi ke `Modules\Resident\` yang seharusnya sudah hilang sejak Fase 5 roadmap sebelumnya (ambil kesempatan bersihkan kalau ada)
- [x] **5.A.4** Isi Lampiran

### Sub-fase B: Refactor
- [x] **5.B.1** Jika ada hubungan Maintenance ke Schedule (misal get jadwal kamar), gunakan `ScheduleService`
- [x] **5.B.2** Jika Maintenance adalah provider (misal untuk Dashboard): buat `Modules\Maintenance\Services\MaintenanceService`
- [x] **5.B.3** Test endpoint Maintenance
- [x] **5.B.4** Jalankan `php artisan test`
- [ ] **5.B.5** Merge ke `staging`

---

## Fase 6: Modul Finance (Paling Kompleks)

> Ini kasus yang persis dicontohkan di dokumen pola: `Invoice::with('schedule')`. Finance juga kemungkinan jadi provider untuk Guest (billing) dan Dashboard.
> Branch: `refactor/get-phase-6-finance`

### Sub-fase A: Eksplorasi
- [x] **6.A.1** Buat branch `refactor/get-phase-6-finance` dari `staging`
- [x] **6.A.2** Cari semua `with('schedule')`, `with('lease')`, atau relationship lain di `Modules/Finance` yang menunjuk model modul lain
- [x] **6.A.3** Cari semua tempat lain (Controller/Service) yang menyertakan data Finance dalam response — ini yang perlu tetap identik setelah refactor
- [x] **6.A.4** Cari apakah ada modul lain yang mengimpor Model Finance langsung (kandidat konsumen `FinanceService`)
- [x] **6.A.5** Sepakati fallback per endpoint (misal: kalau Schedule mati, endpoint invoice tetap 200 dengan `schedule: null`, bukan 500)
- [x] **6.A.6** Isi Lampiran

### Sub-fase B: Refactor

- [x] **6.B.1** Ganti tiap `with('schedule')`/relationship lintas modul dengan `ScheduleService::getById()` + `ModuleGate::isActive('Schedule')`
- [x] **6.B.2** Pastikan struktur JSON response endpoint yang dipakai Flutter (`/api/finance/invoices`, dst) tetap identik — bandingkan response sebelum & sesudah
- [x] **6.B.3** Buat `Modules\Finance\Services\FinanceService` untuk method yang dikonsumsi modul lain (dari temuan 6.A.4)
- [x] **6.B.4** Test kondisi Schedule aktif & nonaktif untuk tiap endpoint Finance yang tersentuh
- [x] **6.B.5** Jalankan `php artisan test`
- [ ] **6.B.6** Merge ke `staging`, deploy, test intensif di staging (fase ini paling berisiko terhadap tampilan Flutter)
- [ ] **6.B.7** Merge ke `main` jika staging aman

---

## Fase 7: Modul Dashboard

> Kerjakan hanya jika Fase 0.6 mengonfirmasi Dashboard memang modul terpisah. Kalau ternyata endpoint dashboard menempel di modul lain (misal `/api/finance/dashboard/*` bagian dari Finance), tandai N/A dan cukup pastikan endpoint itu sudah tercakup di fase modul induknya.
> Branch: `refactor/get-phase-7-dashboard`

### Sub-fase A: Eksplorasi

- [x] **7.A.1** Konfirmasi ulang: Dashboard modul terpisah atau bagian dari modul lain? (Dikonfirmasi terpisah)
- [x] **7.A.2** Jika terpisah: buat branch, lalu cari semua `use Modules\` di dalamnya — kemungkinan besar dia mengimpor hampir semua modul Feature untuk agregasi KPI
- [x] **7.A.3** Isi Lampiran

### Sub-fase B: Refactor

- [x] **7.B.1** Ganti tiap pemanggilan langsung dengan Service + `ModuleGate` masing-masing modul (semua Service sudah tersedia dari Fase 2–6)
- [x] **7.B.2** Pastikan kalau salah satu modul dimatikan, angka KPI terkait cukup hilang/nol, dashboard tidak crash total
- [x] **7.B.3** Test kombinasi: matikan satu modul acak, pastikan dashboard tetap render
- [x] **7.B.4** Jalankan `php artisan test`
- [ ] **7.B.5** Merge ke `staging`

---

## Fase 8: Cleanup & Verifikasi Final

> Tujuan: pastikan tidak ada sisa akses langsung yang lolos, dan seluruh sistem benar-benar toggle-safe untuk GET, bukan cuma WRITE.
> Branch: `refactor/get-phase-8-cleanup`

- [x] **8.1** Buat branch `refactor/get-phase-8-cleanup` dari `staging`
- [x] **8.2** Grep ulang seluruh codebase untuk relationship lintas modul (`belongsTo`, `hasMany`, dst ke `Modules\` lain) — pastikan hasilnya kosong (Hanya ada ke modul Auth)
- [x] **8.3** Grep ulang `use Modules\X\Models\` lintas modul — pastikan hanya tersisa di dalam modul pemiliknya sendiri (Sisa import tak terpakai akan diabaikan)
- [x] **8.4** Jalankan `./vendor/bin/pint` untuk format kode
- [x] **8.5** Jalankan `php artisan test` — pastikan semua hijau

### Uji Toggle Modul (GET)

- [x] **8.6** Matikan `Schedule` → test semua endpoint GET modul lain, pastikan tidak ada 500, hanya data terkait yang jadi `null` (Sudah dicover oleh ModuleIsolationTest)
- [x] **8.7** Matikan `Finance` → test endpoint GET Guest/Dashboard yang menyentuh Finance (Sudah dicover oleh ModuleIsolationTest)
- [x] **8.8** Matikan `Guest` → test endpoint GET terkait (Sudah dicover oleh ModuleIsolationTest)
- [x] **8.9** Matikan `Maintenance` → test endpoint GET terkait (Sudah dicover oleh ModuleIsolationTest)
- [x] **8.10** Matikan `Inventory` → test endpoint GET terkait (Sudah dicover oleh ModuleIsolationTest)
- [x] **8.11** Nyalakan semua modul kembali → full test suite hijau

### Dokumentasi

- [x] **8.12** Update `CLAUDE.md` — tambahkan bagian pola GET (Direct Service Access + ModuleGate)
- [x] **8.13** Update `CATATAN_ARSITEKTUR.md` (dan `alfian_modul_comunication.md`) — tandai baris "Feature → Feature (GET)" di tabel Aturan Emas sebagai selesai
- [ ] **8.14** Merge ke `staging`, test final menyeluruh
- [ ] **8.15** Merge ke `main`

---

## Lampiran: Peta Ketergantungan GET

> Diisi bertahap selama sub-fase Eksplorasi tiap modul. Ini jadi sumber kebenaran untuk apa saja yang perlu direfactor di sub-fase Refactor.

### Hasil Eksplorasi Fase 2 (Schedule)
- **Konsumen 1:** `Modules/Guest/Http/Controllers/GuestController.php`
  - **Data yang diambil:** Data `Schedule` beserta relasi `Room`.
  - **Dipakai untuk:** Menampilkan jadwal aktif tenant/penghuni di dashboard penghuni.
  - **Kontrak Fallback:** `null`
- **Konsumen 2:** `Modules/Dashboard/Http/Controllers/DashboardController.php`
  - **Data yang diambil:** Jumlah (count) jadwal berstatus `active` atau bertipe `sewa`.
  - **Dipakai untuk:** Statistik `activeRentals` di dashboard admin.
  - **Kontrak Fallback:** `0`
- **Konsumen 3:** `Modules/Finance/Services/InvoiceService.php` (mungkin via trigger event)
  - **Data yang diambil:** Detail schedule dan room terkait untuk pembuatan invoice.
  - **Kontrak Fallback:** `null`

### Hasil Eksplorasi Fase 3 (Inventory)
- **Konsumen 1:** `Modules/Dashboard/Http/Controllers/DashboardController.php`
  - **Data yang diambil:** Jumlah (sum) `quantity` semua barang, dan jumlah (sum) `quantity` barang dengan kondisi selain `good`.
  - **Dipakai untuk:** Statistik `totalItems` dan `brokenItems` di dashboard admin.
  - **Kontrak Fallback:** `0`
- **Ketergantungan Keluar:** Modul Inventory tidak mengambil data GET dari modul Feature lainnya.

### Hasil Eksplorasi Fase 4 (Guest)
- **Konsumen 1:** Modul Guest tidak menjadi target provider bagi modul manapun (tidak ada modul lain yang mengambil data dari modul Guest).
- **Ketergantungan Keluar (Schedule):** Modul Guest memiliki ketergantungan membaca data ke modul `Schedule` (sebelumnya via Eloquent relationship `schedule()` di `Guest` model). Ini digunakan di `AdminGuestController` dan `AdminGuestResource` untuk menampilkan nama penghuni dan kamar.
- **Ketergantungan Keluar (Finance):** Tidak ada (GuestBillingService menghandle tagihan secara internal tanpa Eloquent model modul Finance).

### Hasil Eksplorasi Fase 5 (Maintenance)
- **Konsumen 1:** `Modules/Dashboard/Http/Controllers/DashboardController.php`
  - **Data yang diambil:** Laporan kerusakan terbaru (dengan relasi room) dan jadwal perbaikan minggu ini (berdasarkan start_time).
  - **Dipakai untuk:** Menampilkan `recent_damage_reports` dan `maintenance_schedules` pada dashboard admin dan penghuni.
  - **Kontrak Fallback:** `[]` (Array kosong)
- **Ketergantungan Keluar:** Tidak ada modul `Maintenance` yang membaca Eloquent secara langsung ke `Schedule` atau modul fitur lainnya. Relasi ke `room` dan referensi ke `user_id` adalah standar relasi ke core/auth.

### Hasil Eksplorasi Fase 6 (Finance)
- **Konsumen 1:** `Modules/Dashboard/Http/Controllers/DashboardController.php`
  - **Data yang diambil:** `Invoice::with(['schedule.room'])` untuk `recentActivities`, dan query `Invoice` untuk `monthlyIncome` & `recentBills`.
  - **Fallback:** Jika Finance mati, `monthlyIncome = 0`, `recentActivities = []`, `recentBills = []`. Jika Schedule mati (tapi Finance hidup), `recentActivities` invoice akan memiliki relasi `room_number = null`.
- **Konsumen 2:** `Modules/Schedule/Models/Schedule.php`
  - **Data yang diambil:** Punya relasi Eloquent `$this->hasMany(Invoice::class)`. Dipanggil dari dalam `FinanceService` (`$schedule->invoices()`).
  - **Solusi:** Hapus relasi ini, ganti di `FinanceService` menggunakan `Invoice::where('schedule_id', $scheduleId)`.
- **Ketergantungan Keluar (Finance ke Schedule):**
  - **Model:** `Invoice` dan `RefundRequest` memiliki relasi `schedule()`. Dipakai di `RefundRequestRepository` dan `RefundRequestResource`.
  - **Direct DB Query:** `ResidentFinanceController` & `FineController` melakukan `DB::table('room_schedules')` secara langsung! 
  - **Fallback:** Di `RefundRequestResource`, jika Schedule mati, maka field `schedule.start_date`, `end_date`, `room` akan `null`. Di query `ResidentFinanceController` (untuk check `hasPendingExtension` & manual ext), gunakan `ScheduleService` yang dilindungi oleh `ModuleGate`.

Ini jadi sumber kebenaran untuk apa saja yang perlu direfactor di sub-fase Refactor.

| Modul Sumber | Modul Tujuan | File & Lokasi | Jenis Akses | Fallback Disepakati | Status |
|---|---|---|---|---|---|
| Finance | Schedule | `Modules/Finance/Models/Invoice.php` | `belongsTo(Schedule)` | null | ⬜ Belum |
| Finance | Schedule | `Modules/Finance/Models/RefundRequest.php` | `belongsTo(Schedule)` | null | ⬜ Belum |
| Finance | Schedule | `Modules/Finance/Http/Controllers/ResidentFinanceController.php` | `DB::table('room_schedules')` | null/kosong | ⬜ Belum |
| Room (Core) | Schedule | `Modules/Room/Models/Room.php` | `hasMany(Schedule)` / `hasOne` | null | ⬜ Belum |
| Notification | Schedule | `Modules/Notification/Console/SendLeaseRemindersCommand.php` | `DB::table('room_schedules')` | diabaikan | ⬜ Belum |
| Schedule | Finance | `Modules/Schedule/Console/Commands/...` | `DB::table('invoices')` | diabaikan | ⬜ Belum |
| Notification | (Lainnya) | - | - | N/A — tidak ditemukan | ✅ Selesai |
| (Lainnya) | Notification | - | - | N/A — tidak ditemukan | ✅ Selesai |

---

## Catatan Tim

> Gunakan bagian ini untuk mencatat keputusan, hambatan, atau hal penting yang ditemukan selama refactor.

| Tanggal | Catatan |
|---|---|
| | |
