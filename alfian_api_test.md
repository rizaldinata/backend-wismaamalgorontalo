# Laporan Pengujian API & Fungsional (API & Functional Testing)

Dokumen ini merangkum skenario dan hasil pengujian otomatis (*Automated Testing*) untuk modul **Pemeliharaan (Laporan Kerusakan & Jadwal)** dan modul **Inventaris** pada backend Wisma Amal Gorontalo. Semua pengujian menggunakan _framework_ Pest/PHPUnit.

---

## 1. Modul Laporan Kerusakan (Damage Report)

Skenario pengujian ini memastikan bahwa endpoint API untuk pengiriman, pembacaan, dan pembaharuan laporan kerusakan berfungsi sesuai dengan batasan *permission* (hak akses) masing-masing *role*.

### 1.1. Pengujian *Controller / Endpoint API* (`DamageReportControllerTest`)
| No | Skenario Pengujian (Test Case) | Ekspektasi Hasil (Expected Result) | Status |
|----|--------------------------------|------------------------------------|--------|
| 1 | `[BERHASIL] resident dapat mengirim laporan kerusakan baru` | API mengembalikan status `201 Created` dan data laporan baru tersimpan di *database*. | ✅ PASS |
| 2 | `[BERHASIL] resident dapat melihat daftar laporan miliknya` | API mengembalikan data list laporan yang direquest khusus oleh *resident* yang sedang _login_. | ✅ PASS |
| 3 | `[BERHASIL] admin dapat mengupdate status laporan` | Laporan berhasil diperbarui statusnya dan mencatat *log* pembaruan (misal: dari "pending" ke "in_progress"). | ✅ PASS |
| 4 | `[GAGAL] request ditolak jika resident tidak memiliki permission` | Sistem menolak akses dari pengguna (contoh: resident mencoba *update* status milik admin) dengan respons `403 Forbidden`. | ✅ PASS |

### 1.2. Pengujian *Service Logic* (`DamageReportServiceTest`)
| No | Skenario Pengujian (Test Case) | Ekspektasi Hasil (Expected Result) | Status |
|----|--------------------------------|------------------------------------|--------|
| 1 | `[BERHASIL] createReport membuat laporan dan memicu event LaporanKerusakanMasuk` | Data laporan dibuat dan *Event Dispatcher* men-*trigger* `LaporanKerusakanMasuk` secara internal. | ✅ PASS |
| 2 | `[BERHASIL] addUpdate menambah update dan memperbarui status laporan` | Sebuah rekaman *update progres* terbentuk pada tabel terkait dan *status* laporan utama otomatis ter-sinkronisasi. | ✅ PASS |

---

## 2. Modul Jadwal Pemeliharaan (Maintenance Schedule)

Skenario pengujian ini menguji endpoint operasional *Super-Admin/Admin* untuk melakukan penjadwalan pemeliharaan rutin pada aset atau fasilitas.

### 2.1. Pengujian *Controller / Endpoint API* (`ScheduleControllerTest`)
| No | Skenario Pengujian (Test Case) | Ekspektasi Hasil (Expected Result) | Status |
|----|--------------------------------|------------------------------------|--------|
| 1 | `[BERHASIL] admin dapat membuat jadwal pemeliharaan` | API mengembalikan `201 Created` dan jadwal baru terbentuk di *database*. | ✅ PASS |
| 2 | `[BERHASIL] admin dapat menambahkan update pada jadwal` | *Admin* dapat menyisipkan progres/catatan ke sebuah jadwal pemeliharaan yang ada. | ✅ PASS |
| 3 | `[GAGAL] request membuat jadwal ditolak jika tidak memiliki permission` | Upaya tanpa *permission* memadai (misal dari *Resident* atau akun biasa) ditolak dengan respons HTTP `403 Forbidden`. | ✅ PASS |
| 4 | `[BERHASIL] admin dapat melihat daftar jadwal pemeliharaan` | Sistem memvalidasi bahwa daftar pengembalian API memuat koleksi *schedule* dengan format *pagination* yang benar. | ✅ PASS |
| 5 | `[BERHASIL] admin dapat mengubah jadwal pemeliharaan` | Perubahan data (tanggal, deskripsi) berhasil di-*commit* ke tabel jadwal pemeliharaan. | ✅ PASS |
| 6 | `[BERHASIL] admin dapat menghapus jadwal pemeliharaan` | Jadwal beserta catatan progres (*updates*) terkait terhapus atau tertandai *soft-delete*. | ✅ PASS |

### 2.2. Pengujian *Service Logic* (`ScheduleServiceTest`)
| No | Skenario Pengujian (Test Case) | Ekspektasi Hasil (Expected Result) | Status |
|----|--------------------------------|------------------------------------|--------|
| 1 | `[BERHASIL] create membuat jadwal perawatan dan menyisipkan update awal` | *Service* mengeksekusi dua operasi *database* di dalam *transaction* (membuat header jadwal & baris riwayat *update* pertama). | ✅ PASS |
| 2 | `[BERHASIL] addUpdate menambahkan progres dan memperbarui status jadwal` | Memasukkan progres baru sekaligus *auto-update* status *parent* (contoh: jadwal selesai). | ✅ PASS |

---

## 3. Modul Inventaris (Inventory)

Skenario pengujian modul ini memeriksa kapabilitas CRUD inventaris dan memastikan bahwa fitur Inventaris tetap terisolasi/bekerja (*loose coupling*) meskipun modul Keuangan (Finance) tidak aktif.

### 3.1. Pengujian *Controller / Endpoint API* (`InventoryControllerTest`)
| No | Skenario Pengujian (Test Case) | Ekspektasi Hasil (Expected Result) | Status |
|----|--------------------------------|------------------------------------|--------|
| 1 | `[BERHASIL] admin dapat melihat daftar inventaris` | Mengembalikan seluruh *list* koleksi inventaris kamar & fasilitas. | ✅ PASS |
| 2 | `[BERHASIL] admin dapat menambah inventaris baru` | *Payload* barang ter-*submit* sempurna dan tersimpan ke dalam *database*. | ✅ PASS |
| 3 | `[BERHASIL] admin dapat mengubah data inventaris` | Mengizinkan perbaikan kondisi (bagus, rusak, dll) atau pembaruan nama aset. | ✅ PASS |
| 4 | `[BERHASIL] admin dapat menghapus data inventaris` | Data berhasil di-*delete* (berserta rujukan *cascade* jika ada). | ✅ PASS |
| 5 | `[GAGAL] request ditolak jika tidak ada permission view-inventory` | Akses langsung di-blokir pada level autentikasi/otorisasi *middleware* (403). | ✅ PASS |

### 3.2. Pengujian *Service Logic & Isolasi* (`InventoryServiceTest` & `BuatChecklistInventarisSetelahSewaSelesaiTest`)
| No | Skenario Pengujian (Test Case) | Ekspektasi Hasil (Expected Result) | Status |
|----|--------------------------------|------------------------------------|--------|
| 1 | `membuat inventaris dengan purchase_price memicu event InventariBaru` | Jika ada harga pembelian, sistem melempar *Event* `InventariBaru` agar dicatat pengeluarannya. | ✅ PASS |
| 2 | `membuat inventaris tanpa purchase_price tidak memicu event InventariBaru` | Logika me-*bypass* pelemparan *event* pengeluaran jika inventaris adalah gratis (harga nol / `null`). | ✅ PASS |
| 3 | `memperbarui inventaris dengan purchase_price memicu event InventarisDiperbarui` | Memicu *event* pembaruan saat *user* mengganti info harga barang. | ✅ PASS |
| 4 | `menghapus inventaris memicu event InventarisDihapus dengan id yang benar` | Terjadi diseminasi ke modul terkait lewat pesan *broadcast* bahwa ID aset tertentu terhapus. | ✅ PASS |
| 5 | `inventaris terhapus dari database setelah deleteInventory` | Kondisi nyata row pada tabel inventaris di validasi tidak ada lagi. | ✅ PASS |
| 6 | `InventoryService tidak membutuhkan Finance module untuk diinstansiasi` | Sistem tetap dapat memuat (`boot`) *Inventory Service* meski *package* Keuangan tidak ada. | ✅ PASS |
| 7 | `InventoryService dapat digunakan tanpa Finance module aktif` | API dan Fungsi Inventaris beroperasi 100% normal tanpa *hard-error* ketika *Finance* dinonaktifkan (Uji *Decoupling*). | ✅ PASS |
| 8 | `handle tidak melempar exception saat menerima event jadwal sewa selesai` | Memastikan kelancaran penerimaan pesan *broadcast* lintas-modul dari transaksi kamar *check-out*. | ✅ PASS |
| 9 | `listener memiliki method handle yang menerima JadwalSewaSelesai` | *Signature event-listener* terstruktur dan tertulis dengan format argumen yang tepat. | ✅ PASS |

---
**Total Eksekusi:** 30 Skenario Lulus (*Passed*)
**Total Asersi Validasi (*Assertions*):** 64
**Durasi Pengujian:** 1.81 detik
**Kesimpulan:** Seluruh proses logika bisnis dan keamanan modul Laporan Kerusakan, Pemeliharaan, dan Inventaris telah berhasil terverifikasi secara *Automated Testing* di lingkungan sistem *Modular Monolith*.
