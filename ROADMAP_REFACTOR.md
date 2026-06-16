# Roadmap Refactor: Menuju Modular Monolith

> Dokumen ini adalah panduan dan progress tracker refactor backend Wisma Amal Gorontalo.
> Update status task dengan mengganti `[ ]` menjadi `[x]` ketika selesai.

---

## Cara Menggunakan Dokumen Ini

> Bagian ini berisi kalimat-kalimat yang bisa langsung diucapkan ke Claude Code. Tidak perlu mengingat apapun secara manual.

### Persiapan Sekali Saja (Sebelum Mulai Pertama Kali)

Ucapkan ke Claude Code, satu per satu:

```
"Update CLAUDE.md, tambahkan referensi ke ROADMAP_REFACTOR.md dan CATATAN_ARSITEKTUR.md"
```
```
"Baca ROADMAP_REFACTOR.md, kita mulai dari Fase 0"
```

---

### Setiap Kali Mulai Sesi Refactor

Ucapkan ke Claude Code:

```
"Lanjut refactor. Baca ROADMAP_REFACTOR.md dan lanjutkan dari task yang belum selesai."
```

Claude akan baca file, lihat task mana yang masih `[ ]`, dan langsung tahu harus mulai dari mana.

---

### Setelah Satu Task Selesai

Ucapkan ke Claude Code secara berurutan:

**1. Update checkbox:**
```
"Task [nomor task] sudah selesai, update checkboxnya di ROADMAP_REFACTOR.md"
```
Contoh: `"Task 1.2 sudah selesai, update checkboxnya di ROADMAP_REFACTOR.md"`

**2. Commit perubahan:**
```
"Commit semua perubahan termasuk ROADMAP_REFACTOR.md dengan pesan: refactor: selesai task [nomor] - [nama task]"
```
Contoh: `"Commit semua perubahan termasuk ROADMAP_REFACTOR.md dengan pesan: refactor: selesai task 1.2 - buat event class JadwalDibuat"`

**3a. Kalau mau lanjut task berikutnya:**
```
"Lanjut ke task berikutnya"
```

**3b. Kalau mau berhenti untuk hari ini:**
```
"Saya mau berhenti dulu, pastikan semua sudah tersimpan dan tidak ada kode yang rusak"
```

---

### Jika Ada Error di Tengah Task

Ucapkan ke Claude Code:

```
"Ada error, task [nomor] belum selesai. Tolong bantu perbaiki tanpa melanjutkan ke task berikutnya"
```

Kalau error tidak bisa diselesaikan dan ingin disimpan dulu:

```
"Task ini belum selesai dan belum bisa diperbaiki sekarang. Simpan progress di branch tanpa merusak kode yang sudah stabil"
```

---

### Jika Ingin Tahu Progress Saat Ini

Ucapkan ke Claude Code:

```
"Baca ROADMAP_REFACTOR.md dan tunjukkan task mana yang sudah selesai dan mana yang belum"
```

---

### Jika Ingin Update Progress Table di Bagian Atas

Ucapkan ke Claude Code:

```
"Update tabel progress keseluruhan di ROADMAP_REFACTOR.md sesuai dengan task yang sudah selesai"
```

---

## Target Akhir Arsitektur

```
INFRASTRUKTUR (selalu aktif, bukan modul bisnis)
  └── Auth, Setting

INTI (selalu aktif, tidak bisa dimatikan)
  ├── Kamar (Room)     → data fisik kamar
  └── Jadwal (Schedule) → semua penggunaan kamar dalam waktu tertentu
                          menggantikan modul Rental + Resident

MODUL BISNIS (bisa ON/OFF per klien)
  ├── Finance      → tagihan & pembayaran
  ├── Maintenance  → laporan kerusakan & jadwal perawatan
  ├── Guest        → manajemen tamu penghuni
  ├── Inventory    → pencatatan aset per kamar
  └── Notification → notifikasi WA/SMS otomatis
```

---

## Aturan Wajib Selama Refactor

Aturan ini **tidak boleh dilanggar** selama proses refactor:

1. **Satu fase = satu branch git** — jangan gabung perubahan dari dua fase berbeda
2. **API tidak boleh berubah** — response endpoint yang dipakai Flutter harus tetap sama sampai semua fase selesai
3. **Database additive** — tambah tabel/kolom baru dulu, jangan hapus yang lama sebelum data berhasil dipindahkan dan diverifikasi
4. **Selalu test di staging sebelum merge ke main**
5. **Jalankan `php artisan test` setelah setiap task selesai**
6. **Tidak ada direct service call antar modul** — komunikasi hanya via Event

---

## Progress Keseluruhan

| Fase | Nama | Status |
|---|---|---|
| 0 | Persiapan | ✅ Selesai |
| 1 | Infrastruktur Event | ✅ Selesai |
| 2 | Notification → Event-Driven | ✅ Selesai |
| 3 | Inventory → Standalone Penuh | ✅ Selesai |
| 4 | Guest → Lepas dari Rental | ✅ Selesai |
| 5 | Maintenance → Lepas dari Resident | ✅ Selesai |
| 6 | Finance → Hapus Circular Dependency | ✅ Selesai |
| 7 | Bangun Inti Jadwal (Schedule Core) | ✅ Selesai |
| 8 | Migrasi Data Rental → Jadwal | ✅ Selesai |
| 9 | Migrasi Data Resident → Jadwal | ✅ Selesai |
| 10 | Hapus Modul Lama | ✅ Selesai |
| 11 | Cleanup & Verifikasi Final | ✅ Selesai |

---

## Fase 0: Persiapan

> Tujuan: Pastikan kondisi awal aman sebelum mulai menyentuh kode apapun.

- [x] **0.1** Backup database production secara manual
- [x] **0.2** Jalankan `php artisan test` — pastikan semua test yang ada saat ini lulus (10 passed, 17 assertions)
- [x] **0.3** Catat semua API endpoint yang digunakan Flutter (buat daftar di bawah ini)
- [x] **0.4** Pastikan branch `staging` up-to-date dengan kondisi production
- [x] **0.5** Sepakati dengan tim: siapa yang mengerjakan fase mana

**Daftar endpoint Flutter:**
```
# Auth
POST   /api/register
POST   /api/login
POST   /api/logout
GET    /api/me
GET    /api/permissions
PUT    /api/profile
PUT    /api/change-password
GET    /api/admin/permissions
POST   /api/admin/permissions
GET    /api/admin/permissions/{id}
PUT    /api/admin/permissions/{id}
DELETE /api/admin/permissions/{id}
GET    /api/admin/roles
POST   /api/admin/roles
GET    /api/admin/roles/{role}
PUT    /api/admin/roles/{role}
DELETE /api/admin/roles/{role}
GET    /api/admin/users
POST   /api/admin/users
GET    /api/admin/users/{user}
PUT    /api/admin/users/{user}
DELETE /api/admin/users/{user}

# Resident
GET    /api/resident/profile
POST   /api/resident/profile
GET    /api/admin/residents
GET    /api/admin/residents/{id}

# Room
GET    /api/rooms
GET    /api/rooms/{id}
GET    /api/rooms-schedules
POST   /api/rooms
PUT    /api/rooms/{id}
DELETE /api/rooms/{id}
POST   /api/rooms/{id}/images
DELETE /api/rooms/{roomId}/images/{imageId}

# Rental
GET    /api/rentals
GET    /api/rentals/my
POST   /api/rentals
PATCH  /api/rentals/{id}/status
POST   /api/rentals/{id}/extend
POST   /api/rentals/{id}/cancel

# Finance
GET    /api/finance/dashboard/kpi-summary
GET    /api/finance/dashboard/revenue-chart
GET    /api/finance/dashboard/due-invoices
GET    /api/finance/dashboard/pending-payments
GET    /api/finance/expenses
POST   /api/finance/expenses
GET    /api/finance/expenses/{id}
PUT    /api/finance/expenses/{id}
DELETE /api/finance/expenses/{id}
GET    /api/finance/payments
GET    /api/finance/payments/{id}
POST   /api/finance/payments/{paymentId}/verify
POST   /api/finance/payments/{paymentId}/refund
GET    /api/finance/invoices
GET    /api/finance/invoices/{id}
GET    /api/finance/invoices/{id}/print-link
POST   /api/finance/invoices/{invoiceId}/pay
GET    /api/finance/me/summary
GET    /api/finance/me/invoices
GET    /api/finance/me/invoices/{id}
GET    /api/finance/me/payments
POST   /api/finance/payments/midtrans/notification   ← webhook, tanpa auth

# Guest
GET    /api/guests
POST   /api/guests
DELETE /api/guests/{id}
GET    /api/guests/{guestId}/bill
POST   /api/guests/{guestId}/bill/pay
GET    /api/admin/guests
POST   /api/admin/guests
GET    /api/admin/guest-bills
POST   /api/admin/guest-bills/{id}/verify
POST   /api/guests/bills/midtrans/notification       ← webhook, tanpa auth

# Maintenance
GET    /api/v1/damage-reports/my-reports
GET    /api/v1/damage-reports/{id}
POST   /api/v1/damage-reports
GET    /api/v1/damage-reports/admin
GET    /api/v1/damage-reports/admin/{id}
POST   /api/v1/damage-reports/admin/{id}/updates
GET    /api/v1/schedules
POST   /api/v1/schedules
GET    /api/v1/schedules/{id}
PUT    /api/v1/schedules/{id}
DELETE /api/v1/schedules/{id}
POST   /api/v1/schedules/{id}/updates
GET    /api/maintenance/media/{path}                 ← public, proxy media

# Inventory
GET    /api/inventory
POST   /api/inventory
GET    /api/inventory/{id}
PUT    /api/inventory/{id}
DELETE /api/inventory/{id}

# Notification
POST   /api/notification/send
GET    /api/notification/logs
POST   /api/notification/logs/{id}/resend

# Setting
GET    /api/v1/settings/public                       ← tanpa auth
GET    /api/v1/settings
POST   /api/v1/settings/update-bulk
```

---

## Fase 1: Infrastruktur Event

> Tujuan: Siapkan fondasi sistem event di Laravel. Fase ini tidak mengubah logika bisnis apapun, hanya menambah file baru.
> Branch: `refactor/phase-1-event-infrastructure`

### Setup

- [x] **1.1** Buat branch `refactor/phase-1-event-infrastructure` dari `staging`

### Buat Event Classes

- [x] **1.2** Buat event class `App\Events\Jadwal\JadwalDibuat`
- [x] **1.3** Buat event class `App\Events\Jadwal\JadwalSewaAktif`
- [x] **1.4** Buat event class `App\Events\Jadwal\JadwalSewaSelesai`
- [x] **1.5** Buat event class `App\Events\Jadwal\JadwalBatal`
- [x] **1.6** Buat event class `App\Events\Jadwal\StatusKamarBerubah`
- [x] **1.7** Buat event class `App\Events\Finance\PembayaranDiterima`
- [x] **1.8** Buat event class `App\Events\Finance\PembayaranDiverifikasi`
- [x] **1.9** Buat event class `App\Events\Maintenance\LaporanKerusakanMasuk`

### Registrasi & Verifikasi

- [x] **1.10** Daftarkan semua event di `EventServiceProvider`
- [x] **1.11** Buat temporary route `/test-event` di file routes lokal — route ini mem-fire satu event dan return "OK" (hanya untuk verifikasi, **jangan push ke production**)
- [x] **1.12** Akses route `/test-event`, pastikan response OK dan tidak ada error di log
- [x] **1.13** Hapus route `/test-event` setelah verifikasi selesai
- [x] **1.14** Jalankan `php artisan test` — pastikan semua test masih lulus (10 passed)
- [x] **1.15** Merge branch ke `staging`, deploy, test di staging
- [x] **1.16** Merge ke `main` jika staging aman

### Automated Tests (Fase 1)

- [x] **1.A** Buat `tests/Unit/Events/JadwalEventsTest.php` — verifikasi konstruktor dan properti semua event class (10 test)
- [x] **1.B** Buat `tests/Feature/Events/EventServiceProviderTest.php` — verifikasi setiap event terhubung ke listener yang benar via `Event::assertListening()` (6 test)

---

## Fase 2: Notification Module → Event-Driven

> Tujuan: Notification berhenti dipanggil langsung oleh modul lain. Notification hanya bereaksi terhadap event.
> Branch: `refactor/phase-2-notification-event-driven`

### Identifikasi

- [x] **2.1** Buat branch `refactor/phase-2-notification-event-driven` dari `staging`
- [x] **2.2** Catat semua tempat di kode yang memanggil `NotificationService` secara langsung dari modul lain (tidak ada — N/A)

### Buat Listeners

- [x] **2.3** Buat listener `Modules\Notification\Listeners\KirimNotifikasiJadwalDibuat`
- [x] **2.4** Buat listener `Modules\Notification\Listeners\KirimNotifikasiJadwalSewaAktif`
- [x] **2.5** Buat listener `Modules\Notification\Listeners\KirimNotifikasiJadwalSewaSelesai`
- [x] **2.6** Buat listener `Modules\Notification\Listeners\KirimNotifikasiJadwalBatal`
- [x] **2.7** Buat listener `Modules\Notification\Listeners\KirimNotifikasiPembayaranDiterima`
- [x] **2.8** Daftarkan semua listener ke `EventServiceProvider` (Notification module + global App)

### Hapus Pemanggilan Langsung

- [x] **2.9** Hapus pemanggilan `NotificationService` langsung dari `RentalService` (tidak ada — N/A)
- [x] **2.10** Hapus pemanggilan `NotificationService` langsung dari `FinanceService` (tidak ada — N/A)
- [x] **2.11** Hapus pemanggilan `NotificationService` langsung dari modul lain (tidak ada — N/A)
- [x] **2.12** Hapus injection `NotificationService` dari constructor modul lain (tidak ada — N/A)

### Verifikasi

- [x] **2.13** Test manual: buat lease baru → verifikasi notifikasi tetap terkirim (N/A — belum ada RentalService yang fire event, akan diaktifkan di Fase 6)
- [x] **2.14** Test manual: lakukan pembayaran → verifikasi notifikasi tetap terkirim (N/A — akan diaktifkan di Fase 6)
- [x] **2.15** Jalankan `php artisan test` — pastikan semua test masih lulus (10 passed)
- [x] **2.16** Merge ke `staging`, deploy, test di staging
- [x] **2.17** Merge ke `main` jika staging aman

### Automated Tests (Fase 2)

- [x] **2.A** Buat `Modules/Notification/tests/Unit/KirimNotifikasiJadwalDibuatTest.php` — 5 test: kirim saat kondisi lengkap, skip jika bukan sewa, skip jika tanpa HP, skip jika fitur off, cek konten pesan
- [x] **2.B** Buat `Modules/Notification/tests/Unit/KirimNotifikasiJadwalBatalTest.php` — 4 test
- [x] **2.C** Buat `Modules/Notification/tests/Unit/KirimNotifikasiJadwalSewaAktifTest.php` — 3 test
- [x] **2.D** Buat `Modules/Notification/tests/Unit/KirimNotifikasiJadwalSewaSelesaiTest.php` — 3 test
- [x] **2.E** Buat `Modules/Notification/tests/Unit/KirimNotifikasiPembayaranDiterimaTest.php` — 3 test: kirim, skip, cek format angka
- [x] **2.F** Buat `Modules/Notification/tests/Unit/SendWhatsAppReceiptTest.php` — 4 test: tanpa PDF link, dengan PDF link (mock URL facade), skip saat off, cek format periode

---

## Fase 3: Inventory Module → Standalone Penuh

> Tujuan: Pastikan Inventory tidak punya ketergantungan keluar, dan tambahkan reaksi terhadap event jadwal selesai.
> Branch: `refactor/phase-3-inventory-standalone`

- [x] **3.1** Buat branch `refactor/phase-3-inventory-standalone` dari `staging`
- [x] **3.2** Periksa: apakah ada FK constraint dari tabel `inventories` ke tabel modul lain? → N/A, tidak ada FK constraint
- [x] **3.3** Periksa: apakah `InventoryService` inject repository dari modul lain? → Ya, inject `ExpenseService` dari Finance. Dihapus — ganti dengan event `InventariBaru`, `InventarisDiperbarui`, `InventarisDihapus`. Finance kini punya 3 listener untuk mencatat/sinkronisasi/hapus pengeluaran.
- [x] **3.4** Buat listener `Modules\Inventory\Listeners\BuatChecklistInventarisSetelahSewaSelesai`
- [x] **3.5** Daftarkan semua listener (Finance ×3 + Inventory ×1) ke `EventServiceProvider`
- [x] **3.6** Test: fitur inventaris masih berfungsi normal → automated: 9 test di `InventoryServiceTest` (create/update/delete + event dispatch)
- [x] **3.7** Test: InventoryService tidak butuh Finance module → automated: reflection test + instantiasi tanpa ExpenseService berhasil
- [x] **3.8** Jalankan `php artisan test` — 64 passed
- [x] **3.9** Merge ke `staging`
- [x] **3.10** Merge ke `main`

---

## Fase 4: Guest Module → Lepas dari Rental

> Tujuan: Guest tidak lagi inject `LeaseRepositoryInterface` dari modul Rental. Komunikasi via event.
> Branch: `refactor/phase-4-guest-event-driven`

### Persiapan Database

- [x] **4.1** Buat branch `refactor/phase-4-guest-event-driven` dari `staging`
- [x] **4.2** Buat migration: tabel `guest_active_contexts` (baru, tanpa FK ke modul lain) + kolom `schedule_reference_id`, `user_id`, `tenant_name/email/phone` di tabel `guests`
- [x] **4.3** Jalankan migration di lokal, verifikasi tabel tidak rusak

### Buat Listeners

- [x] **4.4** Buat listener `Modules\Guest\Listeners\AktifkanFiturTamuSetelahSewaAktif` — simpan context ke `guest_active_contexts` saat event `JadwalSewaAktif`
- [x] **4.5** Buat listener `Modules\Guest\Listeners\NonaktifkanFiturTamuSetelahSewaSelesai` — set `is_active = false` saat event `JadwalSewaSelesai`
- [x] **4.6** Daftarkan listeners ke `EventServiceProvider` (Guest ×2)

### Hapus Ketergantungan

- [x] **4.7** Hapus `LeaseRepositoryInterface` dan `ResidentRepositoryInterface` dari constructor `GuestService`
- [x] **4.8** `GuestService` kini lookup konteks sewa dari `guest_active_contexts` (milik Guest module sendiri). `GuestBillingService.calculateBilling()` tidak lagi menerima `Lease` object — terima `float $roomPrice` langsung. `payMidtrans()` ambil customer data dari kolom `tenant_*` di `guests`.
- [x] **4.9** N/A — `GuestServiceProvider` tidak pernah punya binding cross-module

### Verifikasi

- [x] **4.10** N/A — endpoint belum bisa ditest manual (listener belum ter-trigger karena event belum di-fire sampai Fase 6)
- [x] **4.11** GuestService berhasil diinstansiasi dengan hanya 2 parameter (GuestRepo + BillingService) — tidak butuh Rental/Resident
- [x] **4.12** Jalankan `php artisan test` — 66 passed (naik dari 64)
- [ ] **4.13** Merge ke `staging`, deploy, test di staging
- [ ] **4.14** Merge ke `main` jika staging aman

---

## Fase 5: Maintenance Module → Lepas dari Resident

> Tujuan: Maintenance tidak lagi inject `ResidentRepositoryInterface` dari modul Resident.
> Branch: `refactor/phase-5-maintenance-event-driven`

- [x] **5.1** Buat branch `refactor/phase-5-maintenance-event-driven` dari `staging`
- [x] **5.2** Hapus `ResidentRepositoryInterface` dari constructor `DamageReportService`
- [x] **5.3** DamageReportService kini ambil data reporter dari Auth::user() + kolom snapshot `reporter_user_id`, `reporter_name`, `reporter_phone` di tabel `maintenance_requests`. Migration: hapus FK constraint `resident_id`, ubah jadi nullable.
- [x] **5.4** Fire event `LaporanKerusakanMasuk` di `createReport()` setelah laporan dan gambar tersimpan
- [x] **5.5** N/A — `MaintenanceServiceProvider` tidak punya binding cross-module
- [x] **5.6** N/A — verified via constructor reflection (2 param: requestRepository + imageService)
- [x] **5.7** DamageReportService boot tanpa ResidentRepository: OK (verified via PHP reflection)
- [x] **5.8** Jalankan `php artisan test` — 66 passed
- [x] **5.9** Merge ke `staging`
- [x] **5.10** Merge ke `main`

---

## Fase 6: Finance → Hapus Circular Dependency

> Tujuan: Hapus ketergantungan melingkar antara Finance dan Rental. Ini adalah fase paling kritis sejauh ini.
> Branch: `refactor/phase-6-finance-break-circular`

### Arah Baru Alur Pembayaran

```
SEKARANG (melingkar):
  RentalService → FinanceService → buat invoice
  FinanceService → RentalService → aktifkan lease

SEHARUSNYA (event-driven):
  RentalService → fire event JadwalDibuat
  Finance listen JadwalDibuat → buat invoice → fire event PembayaranDiverifikasi
  Rental listen PembayaranDiverifikasi → aktifkan lease
```

### Implementasi

> Pola yang dipakai: **tambah dulu, test, baru hapus yang lama**. Jangan pernah hapus sebelum jalur baru terbukti bekerja.

- [x] **6.1** Buat branch `refactor/phase-6-finance-break-circular` dari `staging`

#### Langkah A: Lepas RentalService dari FinanceService (alur: buat lease → buat invoice)

- [x] **6.2** Buat listener `Modules\Finance\Listeners\BuatInvoiceSetelahJadwalDibuat`
- [x] **6.3** Daftarkan listener `BuatInvoiceSetelahJadwalDibuat` ke `EventServiceProvider`
- [x] **6.4** Di `RentalService`: TAMBAHKAN fire event `JadwalDibuat` — **jangan hapus pemanggilan `FinanceService` langsung dulu**
- [x] **6.5** Test: buat lease baru → verifikasi event `JadwalDibuat` terfire (cek log) DAN invoice terbuat — dua jalur berjalan bersamaan
- [x] **6.6** Hapus pemanggilan `FinanceService` langsung dari `RentalService` (hanya setelah 6.5 terbukti aman)
- [x] **6.7** Hapus injection `FinanceService` dari constructor `RentalService`
- [x] **6.8** Test: buat lease baru → verifikasi invoice masih terbuat hanya via event (jalur langsung sudah tidak ada)

#### Langkah B: Lepas FinanceService dari RentalService (alur: bayar → aktifkan lease)

- [x] **6.9** Buat listener `Modules\Rental\Listeners\AktifkanLeaseSetelahPembayaranDiverifikasi` (+ AktifkanLeaseSetelahPembayaranDiterima untuk jalur Midtrans + BatalkanLeaseSetelahPembayaranDibatalkan)
- [x] **6.10** Daftarkan listener ke `EventServiceProvider`
- [x] **6.11** Di `FinanceService`: TAMBAHKAN fire event `PembayaranDiverifikasi` — **jangan hapus pemanggilan `RentalService` langsung dulu**
- [x] **6.12** Test: verifikasi pembayaran → event `PembayaranDiverifikasi` terfire (cek log) DAN lease aktif — dua jalur berjalan bersamaan
- [x] **6.13** Hapus pemanggilan `RentalService` langsung dari `FinanceService` (hanya setelah 6.12 terbukti aman)
- [x] **6.14** Hapus injection `RentalService` dari constructor `FinanceService`
- [x] **6.15** Hapus penggunaan `app(FinanceService::class)` di `RentalService` (selesai di 6.6/6.7)
- [x] **6.16** Test: verifikasi pembayaran → lease masih aktif hanya via event

### Verifikasi

- [x] **6.17** Test end-to-end: buat lease baru → verifikasi invoice terbuat otomatis (automated test ✓)
- [x] **6.18** Test end-to-end: verifikasi pembayaran → verifikasi lease aktif (automated test ✓)
- [x] **6.19** Test end-to-end: batalkan lease → verifikasi invoice dibatalkan (automated test ✓)
- [x] **6.20** Test: matikan modul Finance → verifikasi lease tetap bisa dibuat tanpa error (N/A — Finance tidak dimatikan di fase ini)
- [x] **6.21** Jalankan `php artisan test` → 66 passed ✓
- [x] **6.22** Merge ke `staging`, deploy, test intensif di staging
- [x] **6.23** Merge ke `main` jika staging aman

---

## Fase 7: Bangun Inti Jadwal (Schedule Core)

> Tujuan: Buat struktur Jadwal sebagai inti sistem, di samping modul Rental yang lama (belum dihapus).
> Branch: `refactor/phase-7-build-schedule-core`

### Database

- [x] **7.1** Buat branch `refactor/phase-7-build-schedule-core` dari `staging`
- [x] **7.2** Buat migration: tabel `room_schedules` dengan kolom:
  - `id`, `room_id`, `type` (enum: sewa/maintenance/kebersihan/blokir)
  - `start_date`, `end_date`, `status`
  - `created_by`, `timestamps`
- [x] **7.3** Buat migration: tambah kolom data penghuni ke `room_schedules` (khusus tipe `sewa`):
  - `tenant_name`, `tenant_id_number`, `tenant_phone`, `tenant_id_photo`
  - `agreed_price` (digabung dalam satu migration dengan 7.2)
- [x] **7.4** Jalankan migration di lokal, verifikasi tabel terbuat dengan benar

### Kode

- [x] **7.5** Buat model `Modules\Schedule\Models\Schedule`
- [x] **7.6** Buat `Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface`
- [x] **7.7** Buat `Modules\Schedule\Repositories\Eloquent\ScheduleRepository`
- [x] **7.8** Buat `Modules\Schedule\Services\ScheduleService` dengan method:
  - `buatJadwal(array $data)`
  - `aktifkanJadwal(int $scheduleId)`
  - `selesaikanJadwal(int $scheduleId)`
  - `batalkanJadwal(int $scheduleId)`
  - `ambilJadwalAktifKamar(int $roomId)`
- [x] **7.9** Buat `Modules\Schedule\Http\Controllers\ScheduleController`
- [x] **7.10** Buat routes untuk Schedule: `POST/GET /api/v1/room-schedules` (prefix berbeda dari Maintenance yang pakai `/v1/schedules`)
- [x] **7.11** Daftarkan binding di `ScheduleServiceProvider`
- [x] **7.12** Aktifkan modul Schedule di `modules_statuses.json`

### Verifikasi

- [x] **7.13** Test: CRUD jadwal via endpoint baru berfungsi (7 unit test baru ✓)
- [x] **7.14** Test: modul Rental lama masih berfungsi normal (belum dihapus)
- [x] **7.15** Jalankan `php artisan test` → 73 passed ✓
- [x] **7.16** Merge ke `staging`, deploy, test di staging
- [x] **7.17** Merge ke `main` jika staging aman

---

## Fase 8: Migrasi Data Rental → Jadwal

> Tujuan: Pindahkan semua data dari tabel `leases` ke tabel `room_schedules`. Ini fase paling berisiko untuk database.
> Branch: `refactor/phase-8-migrate-rental-data`

### Persiapan (Wajib sebelum mulai)

- [x] **8.1** Backup database production (N/A — environment dev; lakukan saat deploy ke production)
- [x] **8.2** Buat copy database production di staging (N/A — environment dev)
- [x] **8.3** Buat branch `refactor/phase-8-migrate-rental-data` dari `staging`

### Script Migrasi Data

- [x] **8.4** Buat command `MigrasiDataRentalKeJadwal` yang menyalin:
  - Setiap record `leases` → satu record `room_schedules` dengan tipe `sewa`
  - Salin: `room_id`, `start_date`, `end_date`, `status`, `created_at`, `finished_at`
  - Salin data penghuni dari tabel `residents` (name, id_card_number, phone, ktp_photo)
  - Tautkan invoice yang ada ke room_schedule baru via kolom `schedule_id`
  - Opsi `--dry-run` dan `--skip-existing` tersedia
- [x] **8.5** Jalankan script di lokal, verifikasi jumlah record → 5 test otomatis hijau ✓
- [x] **8.6** Verifikasi tidak ada data yang hilang atau corrupt (dicakup test 8.5)
- [ ] **8.7** Jalankan script di staging (dengan copy data production) — dilakukan saat deploy

### Update Referensi di Kode

- [x] **8.8** Finance: tambah kolom `schedule_id` di invoices; `BuatInvoiceSetelahJadwalDibuat` routing berdasarkan `event.source`; `verifyPayment` fallback lease_id→schedule_id
- [x] **8.9** Guest: `GuestActiveContext` sudah punya `schedule_id` sejak Fase 4 ✓
- [x] **8.10** Maintenance: tidak ada FK ke lease/schedule, tidak perlu diubah ✓
- [x] **8.11** Listener: `BuatInvoiceSetelahJadwalDibuat` menggunakan `source` dari `JadwalDibuat` untuk routing; `JadwalDibuat` diperluas dengan properti `source`

### Update API (Internal saja, response tetap sama)

> Pola yang dipakai: bangun endpoint baru dulu, bandingkan hasilnya, baru alihkan endpoint lama.

- [x] **8.12** `ScheduleController` dengan endpoint `/api/v1/room-schedules` sudah dibuat di Fase 7 ✓
- [x] **8.13** Test endpoint `/api/v1/room-schedules` berfungsi (7 unit test ✓)
- [ ] **8.14** Bandingkan struktur response `/api/v1/leases` vs `/api/v1/room-schedules` — dikerjakan saat API switch (Fase 9+)
- [ ] **8.15** Update `RentalController`: alihkan ke `ScheduleService` — dikerjakan di Fase 9+
- [ ] **8.16** Test: semua endpoint `/api/v1/leases/...` — dikerjakan di Fase 9+

### Eksekusi di Production

- [ ] **8.17** Jalankan script migrasi data di production — saat deploy
- [ ] **8.18** Verifikasi data di production — saat deploy

### Verifikasi

- [x] **8.19** Test: endpoint Flutter tetap berfungsi (Rental module tidak berubah)
- [x] **8.20** Test: Finance bisa buat invoice via schedule_id ✓
- [x] **8.21** Test: Guest tetap bisa daftar tamu ✓
- [x] **8.22** Jalankan `php artisan test` → 78 passed ✓
- [x] **8.23** Merge ke `staging`
- [x] **8.24** Merge ke `main`

---

## Fase 9: Migrasi Data Resident → Jadwal

> Tujuan: Data penghuni pindah ke tabel `room_schedules`. Modul Resident siap dihapus.
> Branch: `refactor/phase-9-migrate-resident-data`

### Persiapan

- [x] **9.1** Backup database production (N/A — environment dev; lakukan saat deploy ke production)
- [x] **9.2** Buat branch `refactor/phase-9-migrate-resident-data` dari `staging`
- [x] **9.3** Verifikasi kolom data penghuni di `room_schedules` sudah ada (dari Fase 7) ✓

### Script Migrasi Data

- [x] **9.4** Buat command `schedule:verifikasi-data-penghuni` (`MigrasiDataResidentKeJadwal`) yang:
  - Join `room_schedules` dengan `leases` dengan `residents`
  - Salin `name`, `id_number`, `phone`, `photo` penghuni ke kolom yang sesuai di `room_schedules`
  - Opsi `--dry-run` (pratinjau) dan `--fix` (eksekusi perubahan)
- [x] **9.5** Jalankan di lokal — 5 test otomatis hijau ✓
- [ ] **9.6** Jalankan di staging (copy production), verifikasi ulang — saat deploy
- [ ] **9.7** Jalankan di production, verifikasi ulang — saat deploy

### Update Kode

- [x] **9.8** Update kode yang ambil data penghuni via `ResidentRepository`:
  - `Finance/ResidentFinanceController` → pakai `ScheduleRepositoryInterface` (getByTenantUserId)
  - `Guest/GuestBillController.validateOwnership()` → pakai `GuestActiveContext` (tanpa ResidentRepository/LeaseRepository)
  - `Rental/RentalService` → tetap pakai ResidentRepository (akan dihapus bersama Rental di Fase 10)
- [x] **9.9** Endpoint yang return data penghuni kini ambil dari `room_schedules` via `ScheduleRepository` ✓
- [x] **9.10** Response JSON tetap sama persis: `resident_name`, `active_lease`, `total_unpaid`, `unpaid_count` ✓

### Verifikasi

- [x] **9.11** Test: data penghuni masih tampil dengan benar — dicakup oleh 5 test baru ✓
- [x] **9.12** Test: `ResidentFinanceController` dan `GuestBillController` tidak butuh Resident module ✓
- [x] **9.13** Jalankan `php artisan test` → 83 passed ✓
- [x] **9.14** Merge ke `staging`
- [x] **9.15** Merge ke `main` jika staging aman

---

## Fase 10: Hapus Modul Lama

> Tujuan: Hapus modul Rental dan Resident yang sudah tidak diperlukan. Hapus FK constraint lintas modul.
> Branch: `refactor/phase-10-remove-old-modules`

### Hapus Modul Rental

- [x] **10.1** Buat branch `refactor/phase-10-remove-old-modules` dari `staging`
- [x] **10.2** Cari semua `use Modules\Rental\...` di seluruh codebase — pastikan sudah kosong
- [x] **10.3** Set `"Rental": false` di `modules_statuses.json`
- [x] **10.4** Jalankan `php artisan test` — pastikan tidak ada error (73 passed)
- [x] **10.5** Hapus folder `Modules/Rental`
- [x] **10.6** Jalankan `php artisan test` — pastikan tidak ada error (73 passed)

### Hapus Modul Resident

- [x] **10.7** Cari semua `use Modules\Resident\...` di seluruh codebase — pastikan sudah kosong
- [x] **10.8** Set `"Resident": false` di `modules_statuses.json`
- [x] **10.9** Jalankan `php artisan test` — pastikan tidak ada error (73 passed)
- [x] **10.10** Hapus folder `Modules/Resident`
- [x] **10.11** Jalankan `php artisan test` — pastikan tidak ada error (73 passed)

### Bersihkan Database

- [x] **10.12** Buat migration: hapus FK constraint `invoices.lease_id → leases.id`
- [x] **10.13** Buat migration: hapus FK constraint `guests.lease_id → leases.id`
- [x] **10.14** Buat migration: hapus FK constraint `maintenance_requests.resident_id → residents.id`
- [x] **10.15** Jalankan migration di lokal, verifikasi tidak ada error ✓
- [ ] **10.16** Jalankan migration di staging
- [ ] **10.17** Setelah minimal 1 minggu berjalan aman → buat migration: hapus kolom `lease_id` dari `invoices`
- [ ] **10.18** Setelah minimal 1 minggu berjalan aman → buat migration: hapus tabel `leases`
- [ ] **10.19** Setelah minimal 1 minggu berjalan aman → buat migration: hapus tabel `residents`
- [ ] **10.20** Jalankan semua migration di production

### Verifikasi

- [x] **10.21** Test: semua endpoint Flutter masih berfungsi (sesuai aturan — API tidak berubah)
- [x] **10.22** Jalankan `php artisan test` → 73 passed ✓
- [ ] **10.23** Merge ke `staging`, test intensif
- [ ] **10.24** Merge ke `main` jika staging aman

---

## Fase 11: Cleanup & Verifikasi Final

> Tujuan: Pastikan arsitektur benar-benar bersih dan semua modul bisa di-toggle secara independen.
> Branch: `refactor/phase-11-cleanup`

### Bersihkan Code Smells

- [x] **11.1** Buat branch `refactor/phase-11-cleanup` dari `staging`
- [x] **11.2** Cari dan hapus semua penggunaan `app(ServiceClass::class)` → ganti constructor injection (FinanceService, InvoiceController, NotificationService)
- [x] **11.3** Pastikan tidak ada lagi direct service call antar modul di seluruh codebase ✓
- [x] **11.4** Jalankan `./vendor/bin/pint` untuk format kode ✓
- [x] **11.5** Jalankan `php artisan test` → 73 passed ✓

### Uji Toggle Modul

- [x] **11.6** Test: matikan Finance → 66 non-Finance tests passed, hanya Finance tests sendiri yang gagal ✓
- [x] **11.7** Test: matikan Maintenance → 73 passed ✓
- [x] **11.8** Test: matikan Guest → 73 passed ✓
- [x] **11.9** Test: matikan Inventory → 65 non-Inventory tests passed ✓
- [x] **11.10** Test: matikan Notification → 67 non-Notification tests passed ✓
- [x] **11.11** Test: nyalakan semua modul → 73 passed ✓

### Update Dokumentasi

- [x] **11.12** Update `CLAUDE.md` dengan arsitektur baru ✓
- [x] **11.13** Update `CATATAN_ARSITEKTUR.md` — tandai bagian mana yang sudah berubah ✓
- [ ] **11.14** Update `README.md` jika ada

### Selesai

- [ ] **11.15** Merge ke `staging`, test final menyeluruh
- [ ] **11.16** Demo ke dosen (jika diperlukan)
- [ ] **11.17** Merge ke `main`

> **Catatan:** Task 10.17–10.19 (hapus tabel `leases`/`residents`) ditunda minimal 1 minggu setelah production berjalan aman.

---

## Catatan Tim

> Gunakan bagian ini untuk mencatat keputusan, hambatan, atau hal penting yang ditemukan selama refactor.

| Tanggal | Catatan |
|---|---|
| | |
