# Catatan Arsitektur: Modular Monolith — Wisma Amal Gorontalo

> Dokumen ini merangkum feedback dosen dan arah refactor yang perlu dilakukan pada backend sistem wisma.

---

## Masalah Utama (Feedback Dosen)

Backend saat ini **belum benar-benar menerapkan arsitektur modular monolith**. Meski folder-foldernya terpisah per modul, secara teknis modul-modul tersebut masih saling bergantung erat sehingga tidak bisa dinyalakan atau dimatikan secara independen.

**Visi dosen:** Ketika ada klien yang membeli aplikasi ini, mereka tinggal pilih modul mana yang diaktifkan sesuai kebutuhan mereka, lalu di-build. Tidak perlu ubah kode.

---

## Konsep Dasar: Dua Sumber Data Inti

Dosen menyatakan bahwa di sistem wisma, hanya ada **dua entitas bisnis yang benar-benar berdiri sendiri**:

### 1. Kamar (Room)
Aset fisik yang dikelola. Menyimpan:
- Nomor, tipe, lantai, harga dasar
- Fasilitas dan foto
- Status: tersedia / terisi / dalam perawatan / diblokir

Kamar **tidak tahu** siapa yang menempatinya. Itu urusan Jadwal.

### 2. Jadwal (Schedule)
Mencatat semua penggunaan kamar dalam rentang waktu tertentu. Setiap entri jadwal memiliki **tipe**:

| Tipe Jadwal | Keterangan |
|---|---|
| `sewa` | Kamar disewa oleh penghuni |
| `maintenance` | Kamar sedang diperbaiki |
| `kebersihan` | Jadwal bersih-bersih rutin |
| `blokir` | Kamar sengaja dikosongkan |

Khusus jadwal tipe `sewa`, data tambahan yang disimpan:
- Nama, KTP, kontak penghuni
- Harga sewa yang disepakati
- Durasi kontrak

> **Penting:** "Resident" dan "Lease/Rental" yang sekarang berdiri sebagai modul terpisah, sebenarnya adalah bagian dari data Jadwal tipe `sewa`. Keduanya bukan entitas independen.

---

## Struktur yang Benar

```
INFRASTRUKTUR (selalu aktif, bukan modul bisnis)
  └── Auth, Setting

INTI (selalu aktif, tidak bisa dimatikan)
  ├── Kamar (Room)
  └── Jadwal (Schedule) ← menggantikan Rental + Resident

MODUL BISNIS (opsional, bisa ON/OFF per klien)
  ├── Finance
  ├── Maintenance
  ├── Guest
  ├── Inventory
  └── Notification
```

---

## Perubahan dari Struktur Sekarang

| Modul Sekarang | Status | Keterangan |
|---|---|---|
| `Auth` | Tetap, jadi Infrastruktur | Bukan modul bisnis |
| `Setting` | Tetap, jadi Infrastruktur | Bukan modul bisnis |
| `Room` | Tetap, jadi Inti | Tidak berubah banyak |
| `Resident` | **Dihapus** | Data penghuni masuk ke Jadwal tipe `sewa` |
| `Rental` | **Diubah jadi Inti Jadwal** | Lease IS jadwal, bukan modul terpisah |
| `Finance` | Tetap, jadi Modul Bisnis | Cara komunikasi diubah (pakai event) |
| `Maintenance` | Tetap, jadi Modul Bisnis | Cara komunikasi diubah (pakai event) |
| `Guest` | Tetap, jadi Modul Bisnis | Cara komunikasi diubah (pakai event) |
| `Inventory` | Tetap, jadi Modul Bisnis | Sedikit penyesuaian |
| `Notification` | Tetap, jadi Modul Bisnis | Mendengarkan semua event |

---

## Masalah Teknis yang Ada Sekarang

### 1. Pemanggilan Langsung Antar Modul (Tight Coupling)
```
RentalService → langsung panggil FinanceService.buatInvoice()
FinanceService → langsung panggil RentalService.aktifkanSewa()
```
Ini berbahaya: ada **ketergantungan melingkar** (circular dependency). Finance butuh Rental, Rental butuh Finance.

### 2. Foreign Key Lintas Modul di Database
```
invoices.lease_id          → leases.id         (Finance tahu struktur DB Rental)
guests.lease_id            → leases.id         (Guest tahu struktur DB Rental)
maintenance_requests.room_id → rooms.id        (Maintenance tahu struktur DB Room)
```
Akibatnya: kalau modul Finance dimatikan dan tabelnya hilang, modul lain bisa error.

### 3. Modul Inject Repository Modul Lain
```
GuestService     ← inject LeaseRepositoryInterface (dari Rental)
MaintenanceService ← inject ResidentRepositoryInterface (dari Resident)
```
Artinya: modul Guest tahu detail internal modul Rental. Kalau Rental berubah, Guest bisa rusak.

---

## Solusi: Komunikasi via Event (Event-Driven)

Alih-alih modul memanggil modul lain secara langsung, setiap modul **mengumumkan kejadian (event)** dan modul lain yang aktif akan **bereaksi**.

### Analogi: Papan Pengumuman
```
Inti Jadwal pasang pengumuman:
  "Jadwal sewa ID-501, Kamar 101, mulai hari ini"

Modul aktif membaca dan bereaksi:
  Finance      → "Ada sewa baru → saya buat invoice"
  Guest        → "Ada sewa baru → saya aktifkan fitur tamu"
  Notification → "Ada sewa baru → saya kirim WA ke penghuni"

Modul yang OFF tidak bereaksi → tidak ada yang error
```

### Daftar Event Penting
| Event | Siapa yang Bereaksi |
|---|---|
| Jadwal sewa dibuat | Finance (buat invoice), Notification (kirim WA) |
| Jadwal sewa aktif (penghuni masuk) | Guest (aktifkan fitur tamu), Notification |
| Jadwal sewa selesai (penghuni keluar) | Finance (tutup tagihan), Inventory (checklist), Notification |
| Jadwal sewa dibatalkan | Finance (cancel invoice), Notification |
| Pembayaran diterima | Notification (konfirmasi ke penghuni) |
| Laporan kerusakan masuk | Inti Jadwal (buat jadwal maintenance), Notification |
| Jadwal maintenance selesai | Inti Jadwal (update status kamar), Notification |

---

## Detail Setiap Modul Bisnis

### Finance
- **Kapan dibutuhkan:** Wisma yang kelola tagihan & pembayaran digital
- **Data milik sendiri:** tabel `invoices`, tabel `payments`
- **Bereaksi terhadap:** event dari Inti Jadwal
- **Referensi ke inti:** simpan `schedule_id` tapi **tanpa FK constraint**
- **Kalau OFF:** tidak ada invoice otomatis, sewa tetap bisa dicatat

### Maintenance
- **Kapan dibutuhkan:** Wisma yang lacak kerusakan & perawatan
- **Data milik sendiri:** tabel `damage_reports`, tabel `maintenance_details`
- **Dua jalur kerja:**
  - Laporan kerusakan → meminta Inti buat jadwal `maintenance`
  - Jadwal rutin → meminta Inti buat jadwal `kebersihan`
- **Kalau OFF:** tidak ada pencatatan kerusakan, sewa tetap berjalan

### Guest
- **Kapan dibutuhkan:** Wisma yang terapkan registrasi tamu
- **Data milik sendiri:** tabel `guests`, tabel `guest_visits`
- **Bereaksi terhadap:** event jadwal sewa aktif/selesai
- **Referensi ke inti:** simpan `schedule_id` tanpa FK constraint
- **Kalau OFF:** tidak ada pencatatan tamu, semua fitur lain tetap jalan

### Inventory
- **Kapan dibutuhkan:** Wisma yang catat aset/barang per kamar
- **Data milik sendiri:** tabel `inventories`, tabel `item_conditions`
- **Bereaksi terhadap:** event jadwal sewa selesai (buat checklist)
- **Kalau OFF:** tidak ada pencatatan inventaris

### Notification
- **Kapan dibutuhkan:** Wisma yang mau notifikasi otomatis WA/SMS
- **Data milik sendiri:** tabel `notification_logs`
- **Bereaksi terhadap:** semua event dari semua modul
- **Kalau OFF:** tidak ada notifikasi otomatis, semua proses bisnis tetap jalan

---

## Uji Validitas: Apakah Benar-benar Modular?

Cara menguji: **matikan satu modul, apakah yang lain tetap jalan?**

| Skenario | Seharusnya |
|---|---|
| Finance OFF | Sewa tetap bisa dibuat, tidak ada invoice |
| Maintenance OFF | Sewa & finance tetap jalan, tidak ada laporan kerusakan |
| Guest OFF | Sewa & finance tetap jalan, tidak ada data tamu |
| Inventory OFF | Semua tetap jalan, tidak ada checklist barang |
| Notification OFF | Semua tetap jalan, tidak ada WA otomatis |

---

## Skenario Klien Berbeda

```
Klien A: Wisma kecil, tidak butuh fitur digital banyak
  ON:  Kamar, Jadwal, Auth, Setting
  OFF: Finance, Maintenance, Guest, Inventory, Notification
  → Bisa catat kamar dan jadwal sewa. Semua jalan normal.

Klien B: Wisma menengah, butuh tagihan digital
  ON:  Kamar, Jadwal, Finance, Notification
  OFF: Maintenance, Guest, Inventory
  → Invoice otomatis dan notifikasi WA aktif.

Klien C: Wisma besar, butuh semua fitur
  ON:  Semua modul
  → Sistem lengkap dan otomatis penuh.
```

---

## Ringkasan Perubahan Teknis yang Diperlukan

1. **Hapus modul Resident** → data penghuni pindah ke tabel jadwal (tipe `sewa`)
2. **Ubah modul Rental menjadi Inti Jadwal** → bukan modul opsional, ini inti sistem
3. **Hapus semua pemanggilan service langsung antar modul** → ganti dengan event
4. **Hapus FK constraint lintas modul di database** → modul hanya simpan referensi ID
5. **Hapus inject repository modul lain** → modul bisnis hanya dengarkan event, tidak akses data modul lain
