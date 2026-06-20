# Prompt: Automated Testing untuk Backend Modular Monolith (Kost Management System)

## Konteks Project

Saya memiliki backend **Laravel** dengan arsitektur **modular monolith** untuk sistem **management kost**. Backend ini baru saja melalui proses refactor besar karena ditemukan kesalahan desain arsitektur sebelumnya. Saya butuh Anda untuk merancang dan mengimplementasikan **automated testing suite yang lengkap dan komprehensif** untuk memastikan hasil refactor ini solid dan tidak regresi di kemudian hari.

### Arsitektur Sistem

Sistem terdiri dari **4 modul utama**:

1. **Module Management Kamar** — mengelola data kamar, tipe kamar, ketersediaan, dll.
2. **Module Management Penghuni** — mengelola data penghuni/penyewa kost.
3. **Module Management Pembayaran** — mengelola transaksi, tagihan, riwayat pembayaran.
4. **Module Management Operasional & Maintenance** — mengelola operasional harian dan permintaan maintenance.

Setiap modul memiliki **banyak fitur granular** di dalamnya, dan setiap fitur tersebut harus bisa **diaktifkan/dinonaktifkan secara independen**.

### Prinsip Arsitektur yang WAJIB Divalidasi oleh Test

Ini adalah requirement inti dari sistem, jadi seluruh test strategy harus berputar di sekitar ini:

- **Module independence**: Jika satu modul dinonaktifkan, ketiga modul lainnya harus tetap berjalan normal tanpa error, tanpa exception, dan tanpa degradasi fungsi yang tidak diinginkan.
- **Feature toggle granular**: Di dalam satu modul, fitur-fitur individual juga harus bisa di-toggle on/off tanpa mempengaruhi fitur lain di modul yang sama maupun modul lainnya.
- **Mekanisme toggle**: Diatur lewat **config file (env/JSON)** — bukan database flag atau service container binding manual. Artinya test harus bisa memanipulasi config ini (misalnya lewat `config()->set()`, override `.env.testing`, atau helper khusus) untuk mensimulasikan berbagai kombinasi modul/fitur aktif-nonaktif.
- **Loose coupling antar modul**: Jika ada modul yang secara fungsional "butuh" data dari modul lain (misalnya Pembayaran butuh data Penghuni), pastikan ketergantungan ini **graceful** — tidak crash, melainkan fallback/skip/notify secara terkendali saat modul yang dibutuhkan nonaktif.

## Tugas Anda

Sebelum menulis satu baris test pun, lakukan tahapan berikut secara berurutan:

### Tahap 1 — Eksplorasi & Pemahaman Codebase
- Pelajari struktur folder modular monolith ini (bagaimana setiap modul dipisahkan — folder per modul, service provider per modul, routing per modul, dll).
- Identifikasi titik di mana config-based toggle ini dibaca/dicek di kode (middleware, service provider boot, route registration, dll).
- Identifikasi semua endpoint API, service class, repository, dan model di tiap modul.
- Identifikasi titik komunikasi/dependency antar modul (event listener, facade, service contract, query langsung ke tabel modul lain, dll) — ini krusial untuk test isolasi modul.

### Tahap 2 — Susun Test Plan (sebelum coding)
Sebelum implementasi, sampaikan dulu ke saya dalam bentuk checklist/outline:
- Daftar modul & fitur yang teridentifikasi beserta titik toggle-nya.
- Daftar dependency antar modul yang ditemukan.
- Strategi test per kategori di bawah ini, disesuaikan dengan apa yang ditemukan di codebase.

Tunggu konfirmasi saya sebelum lanjut ke implementasi penuh, kecuali saya minta langsung jalan.

### Tahap 3 — Implementasi Automated Testing

Gunakan **PHPUnit atau Pest** (ikuti konvensi yang sudah dipakai di project; jika project belum punya konvensi test sama sekali, rekomendasikan salah satu dengan alasan singkat). Buat test suite dengan kategori berikut, **lengkap dari unit hingga integration**:

1. **Unit Test per Modul**
   - Test logic di service class, repository, value object, dan helper di masing-masing modul secara terisolasi (mock dependency eksternal/modul lain).

2. **Feature/Integration Test per Modul (API level)**
   - Test setiap endpoint API per modul: happy path, validation error, authorization, edge case.
   - Gunakan `RefreshDatabase`/`DatabaseTransactions` dan factory/seeder yang representatif.

3. **Module Isolation Test (PALING KRITIS)**
   - Untuk setiap modul, buat test skenario: nonaktifkan modul tersebut via config, lalu pastikan:
     - Endpoint/route modul tersebut tidak bisa diakses (404/403, sesuai desain) dan tidak menyebabkan fatal error di request lain.
     - Ketiga modul lainnya tetap berfungsi normal (jalankan ulang sebagian test feature modul lain dalam kondisi ini).
     - Tidak ada exception/error 500 yang muncul akibat dependency yang hilang.
   - Lakukan ini untuk **semua kombinasi realistis** (minimal: tiap modul dinonaktifkan satu per satu; jika resource memungkinkan, beberapa kombinasi gabungan).

4. **Feature Flag Test (granular dalam modul)**
   - Untuk fitur-fitur granular di dalam tiap modul, test bahwa toggle on/off masing-masing fitur tidak saling mengganggu fitur lain di modul yang sama.

5. **Cross-Module Contract/Dependency Test**
   - Untuk titik-titik dependency antar modul yang ditemukan di Tahap 1, test perilaku "graceful degradation"-nya secara eksplisit (bukan cuma "tidak crash", tapi pastikan behavior yang dihasilkan sesuai ekspektasi bisnis — misalnya fallback value, pesan informatif, atau fitur ter-skip).

6. **Regression Test Hasil Refactor**
   - Jika ada dokumentasi/catatan tentang desain arsitektur lama yang salah, pastikan ada test yang secara spesifik mengkonfirmasi bug/masalah desain tersebut **tidak muncul lagi** di arsitektur baru.

### Tahap 4 — Output & Dokumentasi
- Susun struktur folder test yang mengikuti struktur modul (misalnya `tests/Feature/{ModuleName}/...`, `tests/Unit/{ModuleName}/...`, dan folder khusus `tests/Feature/ModuleIsolation/...`).
- Sertakan helper/trait reusable untuk manipulasi config toggle saat testing (agar tidak duplikasi di banyak test file).
- Berikan ringkasan coverage di akhir: modul/fitur apa yang sudah tercover, dan apa yang masih butuh perhatian manual (jika ada).

## Batasan & Catatan Tambahan

- Jangan mengubah logic production code kecuali untuk memperbaiki bug yang ditemukan saat proses testing — dan jika itu terjadi, laporkan dulu ke saya sebelum mengubah, jangan diam-diam diperbaiki.
- Gunakan factory/seeder yang representatif terhadap domain kost management (kamar, penghuni, pembayaran, maintenance) — bukan data dummy generik.
- Jika menemukan modul yang ternyata masih punya hard dependency ke modul lain (melanggar prinsip independence), laporkan sebagai temuan terpisah, jangan langsung "dipaksa lolos" test.