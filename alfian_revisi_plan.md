# Daftar Task Revisi Sidang (Bagus Alfian)

Dokumen ini berisi daftar rincian pekerjaan (task) yang akan kita kerjakan secara bertahap berdasarkan catatan revisi sidang dari **Pak Ferry** dan **Pak Andik**, difokuskan pada area modul **Maintenance** dan **Inventory**.

---

## ✅ Selesai Dikerjakan (Di Aplikasi)
- [x] **Pak Andik**: Memperbaiki format uang agar tidak disingkat.
- [x] **Pak Andik**: Menambahkan fitur pagination pada tabel.
- [x] **Pak Andik**: Memperjelas judul tabel.
- [x] **Bugfix Tambahan**: Menambahkan *field* "Lokasi Kerusakan" pada form dan halaman Detail Laporan Maintenance.

---

## 🛠️ Fase 1: API & Functional Testing (Engineering)
Fokus pada pembuatan *automated testing* agar bisa discreenshot dan dilampirkan sebagai bukti "Hasil Uji" di buku laporan.

- [ ] **Task 1.1**: Membuat API Feature Test (PHPUnit) untuk endpoint modul `Maintenance` (termasuk laporan kerusakan & jadwal pemeliharaan).
- [ ] **Task 1.2**: Membuat API Feature Test (PHPUnit) untuk endpoint modul `Inventory` (pengelolaan barang).
- [ ] **Task 1.3**: Memastikan semua pengujian services/API berjalan dengan status hijau (`PASSED`) dan mencetak rekapitulasi hasilnya untuk lampiran buku.

---

## 📚 Fase 2: Pembuatan Draft Penjelasan untuk Buku (Dokumentasi)
Fokus pada menyusun draf narasi teknis baku dengan tata tulis (huruf kapital) yang benar sesuai pedoman laporan, agar Anda tinggal menyalinnya (*copy-paste*).

- [ ] **Task 2.1**: Menulis penjelasan detail mengenai **Desain Services (Endpoint)** untuk modul Maintenance & Inventory (method, URL, deskripsi, request/response).
- [ ] **Task 2.2**: Menulis penjelasan mengenai **Aliran Data (Data Flow)** antar modul dalam arsitektur *Modular Monolith*.
- [ ] **Task 2.3**: Menulis penjelasan teknis mengenai **Alur Aktif / Non-aktifkan Modul** (mekanisme *Feature Toggle* di sistem).
- [ ] **Task 2.4**: Menulis dokumentasi penjelasan mengenai **Proses Deployment** aplikasi ke sisi *user* (berdasarkan *workflow* yang ada di project).

---

## 🖼️ Fase 3: Pembaruan Skema Database (Visualisasi ERD)
Fokus pada pembuatan struktur relasi database yang bersih per modul.

- [ ] **Task 3.1**: Membuat kode visualisasi diagram ERD (menggunakan *PlantUML* atau *Mermaid*) khusus untuk entitas di dalam modul **Maintenance** (Tabel Request, Schedule, Images, Updates, dll).
- [ ] **Task 3.2**: Membuat kode visualisasi diagram ERD khusus untuk entitas di dalam modul **Inventory** (Tabel Items, Categories, Stock, dll).

---

> [!TIP]
> **Cara Melanjutkan:**
> Beri tahu saya jika Anda ingin memulai dari **Fase 1** (membuat testing API), **Fase 2** (menyusun teks dokumentasi), atau **Fase 3** (membuat ERD). Saya sarankan kita mulai dari Fase 1 agar bagian pengkodeannya selesai 100%.
