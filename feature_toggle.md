# Prompt: Implementasi Dynamic Feature Toggle System (Config File, Auto-Reload, Owner-Controlled)

## Konteks Project

Saya memiliki sistem management kost **Wisma Amal Gorontalo** dengan backend **Laravel** (arsitektur modular monolith) dan frontend **Flutter Web**. Mekanisme toggle modul/fitur sudah **didesain** menggunakan **config file (JSON)**, dan strukturnya **sudah saya sediakan di project** — tugas Anda adalah **mengimplementasikan logic-nya**, bukan mendesain ulang dari nol.

Yang membedakan kebutuhan ini dari toggle modul "statis" yang sudah ada sebelumnya: sekarang **owner kost** (bukan developer) harus bisa menyalakan/mematikan modul maupun fitur granular di dalamnya **langsung dari UI Flutter**, **tanpa restart server, tanpa deploy ulang, dan tanpa edit file manual**. Jadi config file ini harus bisa **dibaca dan ditulis ulang secara aman saat aplikasi sedang berjalan (runtime)**.

### Granularity yang Dibutuhkan

Toggle harus mendukung dua level sekaligus:
1. **Level Modul** — mematikan satu modul penuh (Finance, Schedule, Room, Maintenance, dst).
2. **Level Fitur Granular** — mematikan fitur spesifik di dalam satu modul (misalnya di dalam modul Finance: matikan hanya "Pembayaran via Midtrans" tapi modul Finance & fitur lain di dalamnya tetap jalan).

**Aturan hierarki yang wajib dipatuhi**: jika modul induk dimatikan, maka seluruh fitur granular di dalamnya otomatis dianggap nonaktif juga — terlepas dari status toggle individual fitur tersebut di file config. Fitur granular hanya efektif "aktif" jika modul induknya juga aktif.

## Tugas Anda

### Tahap 1 — Eksplorasi Codebase (WAJIB sebelum coding)

- Cari dan pelajari struktur file config toggle yang **sudah saya sediakan** di project (kemungkinan ada di `config/`, `storage/app/`, atau lokasi custom — telusuri dulu).
- Pelajari `Module Setting` dan `SettingService` yang mengimplementasikan `ConfigProviderInterface` — ini adalah komponen yang sudah saya rancang sebagai titik akses config. Pahami kontrak interface-nya sebelum mengubah apa pun.
- Identifikasi semua titik di kode (middleware, service provider, route registration, policy/gate) yang saat ini membaca status toggle modul/fitur, agar implementasi baru tetap kompatibel dengan titik-titik tersebut.
- Laporkan temuan Anda dalam bentuk ringkasan singkat sebelum lanjut ke Tahap 2, supaya saya bisa konfirmasi pemahaman Anda terhadap desain yang sudah ada sudah benar.

### Tahap 2 — Backend: Mekanisme Baca & Tulis Config secara Aman saat Runtime

Ini bagian paling kritis. Perhatikan baik-baik beberapa masalah teknis berikut dan pastikan solusinya ditangani eksplisit:

1. **Jangan gunakan Laravel config cache (`php artisan config:cache`) untuk file toggle ini.** Jika file toggle didaftarkan lewat mekanisme `config()` bawaan Laravel yang di-cache, perubahan tidak akan ter-reflect tanpa clear cache manual. Pastikan `SettingService`/`ConfigProviderInterface` membaca file JSON **langsung dari disk pada setiap request** (atau dengan cache request-level/in-memory yang sangat singkat, bukan cache persisten lintas request), sehingga toggle baru langsung berlaku begitu file ditulis ulang — inilah yang dimaksud "auto-reload tanpa restart".
2. **Atomic write**: saat menulis ulang file config (misalnya owner toggle dari UI), JANGAN langsung overwrite file asli. Tulis ke file temporer dulu, lalu `rename()` ke nama file asli. Ini mencegah file menjadi corrupt/setengah-tertulis jika proses terputus di tengah jalan (misalnya request timeout).
3. **File locking**: gunakan locking (`flock` atau mekanisme setara) saat proses tulis, untuk mencegah race condition jika ada dua request update toggle bersamaan.
4. **Validasi skema sebelum menulis**: sebelum file baru ditulis, validasi struktur JSON-nya (semua modul/fitur yang wajib ada tidak hilang, tipe data boolean, dll). Jika validasi gagal, tolak penulisan dan kembalikan error jelas — JANGAN sampai file config rusak dan menyebabkan seluruh aplikasi gagal boot.
5. **Fallback/default value**: jika file config tidak ditemukan atau gagal di-parse saat aplikasi jalan, sistem harus punya default values yang aman (semua modul inti tetap nyala) agar aplikasi tidak crash total — jangan biarkan satu file korup menjatuhkan seluruh sistem.
6. **Lokasi file**: pastikan file toggle ini disimpan di lokasi yang writable oleh user web server (misalnya `storage/app/`), bukan di folder yang biasanya read-only di production atau ter-track ketat oleh git deployment (hindari taruh di `config/` Laravel bawaan kalau itu akan tertimpa saat deploy). Sesuaikan dengan struktur yang sudah saya sediakan, tapi flag ke saya kalau lokasinya berisiko bermasalah di production.

### Tahap 3 — Backend: API Endpoint untuk Owner

Buat endpoint API (di bawah middleware otorisasi role `Super-Admin`/pemilik saja) untuk:

1. **GET status toggle saat ini** — return struktur lengkap modul beserta fitur granular di dalamnya, termasuk status efektif (mempertimbangkan aturan hierarki di atas, bukan cuma nilai mentah dari file).
2. **PATCH/PUT update toggle** — menerima payload perubahan (bisa toggle satu modul, atau satu fitur granular), lakukan validasi, lalu tulis ulang file dengan mekanisme aman dari Tahap 2.
3. Pertimbangkan menambahkan **audit trail ringan** (siapa yang mengubah, kapan) — bisa disimpan terpisah dari file config itu sendiri (misalnya log file atau tabel database kecil khusus log, BUKAN bagian dari file toggle), supaya owner bisa lihat histori perubahan tanpa mengotori struktur config.
4. Pastikan endpoint ini **tidak bisa diakses role lain selain pemilik/super-admin**, karena ini kontrol sensitif yang mempengaruhi seluruh sistem.

### Tahap 4 — Flutter: Halaman Pengaturan Fitur untuk Owner

Bangun halaman UI (mengikuti Clean Architecture yang sudah diterapkan di project — Presentation/Domain/Data layer, BLoC untuk state management) dengan kebutuhan berikut:

1. **Tampilan hierarkis**: daftar modul sebagai item utama dengan switch toggle, dan di bawah/dalam setiap modul ada daftar fitur granular dengan switch toggle masing-masing (bisa expandable/collapsible per modul).
2. **State dependency visual**: jika modul induk dalam keadaan nonaktif, switch fitur granular di dalamnya harus tampil disabled/abu-abu (meski nilai mentahnya di backend tidak diubah), untuk mencerminkan aturan hierarki secara jelas ke owner — hindari kebingungan "kenapa saya nyalakan tapi tetap nggak jalan".
3. **Konfirmasi sebelum mematikan modul**: karena mematikan satu modul berdampak besar (semua fitur di dalamnya ikut nonaktif), tampilkan dialog konfirmasi yang menjelaskan dampaknya sebelum eksekusi — terutama jika modul tersebut punya dependency yang diketahui dari modul lain (misal Schedule bergantung pada Room).
4. **Optimistic update dengan rollback**: saat owner toggle switch, update UI langsung (optimistic), kirim request ke API, dan rollback otomatis ke state sebelumnya jika request gagal — sertai pesan error yang jelas.
5. **Loading & error state** yang jelas saat fetch status toggle awal (gunakan BLoC states: Loading, Loaded, Error sesuai konvensi project).
6. **Indikator real-time**: tampilkan info kapan terakhir kali pengaturan diubah (dan oleh siapa, jika audit trail di Tahap 3 diimplementasikan).

### Tahap 5 — Integrasi dengan Automated Testing yang Sudah Ada

Project ini sudah memiliki automated testing (Pest/PHPUnit) termasuk **Module Isolation Test** yang sebelumnya memanipulasi config secara langsung di level test (misalnya lewat `config()->set()` atau override env file). Karena sekarang mekanisme baca config berubah (baca langsung dari file JSON di disk, bukan lagi `config()` Laravel biasa):

- Sediakan **helper/trait testing** baru yang memudahkan test untuk override isi file toggle dengan aman (tulis ke file test sementara, lalu kembalikan ke kondisi semula setelah test selesai — jangan sampai test "mengotori" file config asli).
- Update test Module Isolation yang sudah ada agar tetap kompatibel dengan mekanisme baca-tulis file yang baru ini, sekaligus tambahkan skenario test baru khusus untuk:
  - Toggle granular fitur (bukan cuma modul penuh).
  - Validasi bahwa fitur granular tetap nonaktif meski di-toggle aktif individual, selama modul induknya nonaktif (aturan hierarki).
  - Endpoint API toggle ditolak untuk role selain Super-Admin.
  - File config corrupt/tidak ditemukan tetap menghasilkan fallback default yang aman, bukan crash.

### Tahap 6 — Dokumentasi

Setelah implementasi selesai, ringkaskan untuk saya:
- Struktur final skema JSON config (lengkap dengan contoh).
- Lokasi file dan alasan pemilihan lokasinya.
- Daftar endpoint API baru beserta contoh request/response.
- Catatan risiko/keterbatasan dari pendekatan file-based config ini (misalnya: tidak ideal jika nanti server di-scale jadi multi-instance/load balanced, karena tiap instance punya filesystem lokal sendiri-sendiri kecuali pakai shared storage — sampaikan ini ke saya sebagai catatan untuk pertimbangan masa depan, tidak perlu diselesaikan sekarang).

## Batasan & Catatan Tambahan

- Jangan ganti pendekatan "config file" ke database tanpa konfirmasi saya — ini keputusan yang sudah saya ambil secara sadar.
- Jangan ubah struktur `ConfigProviderInterface` yang sudah ada kecuali benar-benar diperlukan; jika perlu, jelaskan dulu alasannya ke saya sebelum mengubah kontraknya, karena interface ini kemungkinan dipakai di banyak tempat.
- Saat Anda menemukan modul yang ternyata punya hard dependency ke modul lain (melanggar prinsip independence dari hasil refactor sebelumnya) saat mengimplementasikan toggle granular, laporkan sebagai temuan terpisah — jangan dipaksa "kelihatan jalan" padahal sebenarnya rapuh.
- Ikuti konvensi penamaan, struktur folder, dan state management (BLoC) yang sudah ada di project Flutter — jangan perkenalkan pattern baru tanpa alasan kuat.
- Setelah implementasi, jalankan `dart analyze` di sisi Flutter dan pastikan tidak ada warning/error baru, sesuai rules yang sudah saya terapkan di project.