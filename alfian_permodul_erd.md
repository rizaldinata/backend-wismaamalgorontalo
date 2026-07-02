# Entity Relationship Diagram (ERD) - Fase 3

Dokumen ini berisi visualisasi struktur tabel database (ERD) untuk modul **Pemeliharaan (Maintenance)** dan modul **Inventaris (Inventory)** pada sistem Wisma Amal Gorontalo. 

Diagram ini di-generate menggunakan format standar *Mermaid.js* sehingga mudah disisipkan di berbagai alat dokumentasi (seperti GitHub, Notion, atau aplikasi Markdown lainnya) dan *screenshot*-nya bisa langsung Anda lampirkan di skripsi.

---

## 3.1 ERD Modul Pemeliharaan (Maintenance)

Modul Pemeliharaan memiliki struktur data yang cukup kompleks karena memisahkan antara Laporan Kerusakan (dari penyewa) dan Jadwal Pemeliharaan (dari pengelola), namun keduanya bisa memiliki riwayat perkembangan/progres (*updates*) dan lampiran bukti foto (*images*).

```mermaid
erDiagram
    %% Tabel Eksternal (Sebagai Referensi)
    USERS {
        bigint id PK
        string name
        string role
    }
    ROOMS {
        bigint id PK
        string room_number
    }

    %% Tabel Utama Modul Maintenance
    MAINTENANCE_REQUESTS {
        bigint id PK
        bigint resident_user_id FK "Referensi ke USERS"
        bigint room_id FK "Referensi ke ROOMS (Opsional)"
        string location "Lokasi kerusakan (Teks)"
        string issue_type "Jenis Masalah (plumbing, electrical, dll)"
        text description
        string status "Status (pending, in_progress, dll)"
        datetime created_at
    }

    MAINTENANCE_REQUEST_IMAGES {
        bigint id PK
        bigint maintenance_request_id FK
        string image_path
        datetime created_at
    }

    MAINTENANCE_REQUEST_UPDATES {
        bigint id PK
        bigint maintenance_request_id FK
        bigint admin_user_id FK "Admin yang mengubah"
        string status
        text notes "Catatan progres"
        datetime created_at
    }

    MAINTENANCE_SCHEDULES {
        bigint id PK
        string title
        text description
        date start_date
        date end_date
        string status "Status (scheduled, in_progress, completed)"
        datetime created_at
    }

    MAINTENANCE_SCHEDULE_UPDATES {
        bigint id PK
        bigint maintenance_schedule_id FK
        bigint admin_user_id FK
        string status
        text notes
        datetime created_at
    }

    MAINTENANCE_UPDATE_IMAGES {
        bigint id PK
        bigint update_id FK "ID dari Request Update atau Schedule Update"
        string update_type "Tipe (Polymorphic: Request / Schedule)"
        string image_path
        datetime created_at
    }

    %% Relasi
    USERS ||--o{ MAINTENANCE_REQUESTS : "melaporkan"
    ROOMS ||--o{ MAINTENANCE_REQUESTS : "memiliki"
    
    MAINTENANCE_REQUESTS ||--o{ MAINTENANCE_REQUEST_IMAGES : "memiliki lampiran"
    MAINTENANCE_REQUESTS ||--o{ MAINTENANCE_REQUEST_UPDATES : "memiliki riwayat"
    
    MAINTENANCE_REQUEST_UPDATES ||--o{ MAINTENANCE_UPDATE_IMAGES : "melampirkan foto progres"
    
    MAINTENANCE_SCHEDULES ||--o{ MAINTENANCE_SCHEDULE_UPDATES : "memiliki riwayat"
    MAINTENANCE_SCHEDULE_UPDATES ||--o{ MAINTENANCE_UPDATE_IMAGES : "melampirkan foto progres"

```

### Penjelasan Relasi:
1. Setiap **Laporan Kerusakan (`maintenance_requests`)** terikat ke seorang **Penghuni (`users`)** dan dapat terikat pada sebuah **Kamar (`rooms`)**. 
2. Sebuah laporan kerusakan dapat memiliki **banyak lampiran foto (`maintenance_request_images`)** sebagai bukti saat melapor.
3. Saat laporan dikerjakan, admin menambahkan **riwayat progres (`maintenance_request_updates`)**, yang juga dapat dilampirkan **foto hasil perbaikan (`maintenance_update_images`)**.
4. Logika yang sama berlaku untuk **Jadwal Pemeliharaan (`maintenance_schedules`)**, di mana setiap perawatan memiliki catatan histori pembaruan beserta bukti foto selesainya.

---

## 3.2 ERD Modul Inventaris (Inventory)

Modul Inventaris dibuat sangat *independent* (berdiri sendiri) dan *loose coupling* agar dapat berfungsi meskipun modul Keuangan (Finance) tidak ada. Referensi ruangannya juga bersifat opsional jika barang diletakkan di area luar kamar.

```mermaid
erDiagram
    %% Tabel Eksternal
    ROOMS {
        bigint id PK
        string room_number
    }

    %% Tabel Utama Modul Inventory
    INVENTORIES {
        bigint id PK
        bigint room_id FK "Referensi ke ROOMS (Opsional/Gudang)"
        string item_name "Nama barang"
        string condition "Kondisi (excellent, good, damaged, lost)"
        decimal purchase_price "Harga Beli"
        date purchase_date "Tanggal Pembelian"
        text notes "Catatan tambahan"
        datetime created_at
        datetime updated_at
    }

    %% Relasi
    ROOMS ||--o{ INVENTORIES : "berisi"
```

### Penjelasan Relasi:
1. Tabel **Inventaris (`inventories`)** dapat dihubungkan ke **Kamar (`rooms`)** melalui relasi *One-to-Many* (Satu kamar bisa berisi banyak inventaris).
2. Jika *field* `room_id` bernilai `null`, ini berarti barang inventaris tersebut sedang berada di gudang pusat, fasilitas umum (lobi/dapur), atau merupakan aset lepas yang belum ditugaskan ke sebuah kamar.
3. Meskipun terdapat *field* `purchase_price`, secara ERD tabel ini **tidak berelasi langsung (FK)** dengan tabel pengeluaran (Finance) untuk menjaga kemurnian pemisahan *Modular Monolith*. Integrasi dengan data keuangan sepenuhnya dilakukan dengan cara saling melempar *Event Listener* di level sistem.
