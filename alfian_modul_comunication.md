# Komunikasi Antar Modul (Modular Monolith)

Dalam arsitektur *Modular Monolith* (khususnya menggunakan *package* `nwidart/laravel-modules` seperti pada *backend* Wisma Amal Gorontalo), ada aturan ketat agar setiap modul tetap independen (tidak saling mengunci/*tightly coupled*).

Berdasarkan struktur kode Anda, terdapat **2 pola komunikasi utama** antar modul:

## 1. Komunikasi Sinkron / *Synchronous* (Untuk GET Data)
Saat sebuah modul hanya perlu **membaca data (GET)** dari modul lain, komunikasi dilakukan secara langsung melalui *Eloquent Relationships*, *Repository*, atau pemanggilan *Model* antar *namespace*.

**Contoh Kasus:** Modul `Maintenance` ingin menampilkan detail `DamageReport` beserta nama pelapornya (yang berada di modul `Auth` atau kelak `Users`).

```mermaid
sequenceDiagram
    autonumber
    actor Client as Frontend / Mobile
    participant C as DamageReportController<br>(Modul Maintenance)
    participant M1 as DamageReport Model<br>(Modul Maintenance)
    participant M2 as User Model<br>(Modul Auth/Users)
    participant DB as Database

    Client->>C: GET /api/v1/damage-reports
    Note over C, M2: Modul Maintenance meminjam Model dari Modul lain
    C->>M1: DamageReport::with('user')->get()
    M1->>M2: BelongsTo(User::class)
    M2->>DB: SELECT * FROM damage_reports JOIN users
    DB-->>M1: Raw Data
    M1-->>C: Eloquent Collection (Reports + User Data)
    C-->>Client: JSON Response
```

**Kenapa boleh langsung?** 
Membaca data (*Read*) tidak mengubah stat (*State*) aplikasi, sehingga aman memanggil *Model/Repository* dari modul lain secara langsung. Ini membuat *query* sangat efisien (bisa memanfaatkan `JOIN` atau *Eager Loading* di level database).

---

## 2. Komunikasi Berbasis Event / *Event-Driven* (Untuk ADD/UPDATE/DELETE Data)
Saat sebuah modul **menambah atau mengubah data (Write)** dan perubahan tersebut memiliki efek samping ke modul lain, komunikasi **TIDAK BOLEH** dilakukan secara langsung (memanggil *Controller/Service* modul lain). Hal ini agar jika modul tujuan dimatikan, modul asal tidak mengalami *error* atau *crash*.
Solusinya adalah menggunakan **Laravel Events & Listeners**.

**Contoh Kasus:** *Admin* menambah `Inventory` baru yang memiliki `purchase_price` di modul `Inventory`. Modul `Finance` (Keuangan) perlu mencatat itu sebagai pengeluaran (*Expense*).

```mermaid
sequenceDiagram
    autonumber
    actor Client as Frontend / Mobile
    participant C as InventoryController<br>(Modul Inventory)
    participant S as InventoryService<br>(Modul Inventory)
    participant DB as Database
    participant EV as Laravel Event Dispatcher<br>(Core System)
    participant L as InventarisBaruListener<br>(Modul Finance)

    Client->>C: POST /api/inventory (Barang Baru)
    C->>S: createInventory(data)
    
    %% Proses Simpan Inventaris
    S->>DB: INSERT INTO inventories
    DB-->>S: Success (inventory_id)
    
    %% Event Dispatch
    Note over S, EV: Modul Inventory hanya "berteriak" ke sistem
    S->>EV: event(new InventariBaru($inventory))
    
    %% Modul Inventory langsung return response tanpa menunggu Finance
    S-->>C: Inventory Object
    C-->>Client: 201 Created
    
    %% Reaksi Modul Finance (Listener)
    Note right of EV: Terjadi di luar sepengetahuan Modul Inventory
    EV->>L: trigger handle(InventariBaru)
    L->>DB: INSERT INTO expenses (purchase_price)
```

**Kelebihan Pendekatan Ini (*Loose Coupling*):**
1. **Tidak Bergantung:** Modul `Inventory` sama sekali tidak perlu mengimpor `FinanceService` atau tahu menahu soal tabel `expenses`.
2. **Aman Dimatikan:** Jika modul `Finance` dinonaktifkan (di `modules_statuses.json`), API Tambah Inventaris akan **tetap berjalan normal** (status 201). Modul `Inventory` hanya menyebarkan *event*, jika tidak ada modul `Finance` yang "mendengar", sistem akan mengabaikannya dengan aman (sudah teruji di *Unit Test*).

---

## Kesimpulan Aturan Emas (Golden Rule)
Di *backend* Anda, tim pengembang menerapkan aturan:
- **Butuh Baca Data Tetangga?** $\rightarrow$ *Direct Call* (Gunakan Model/Relasi `belongsTo`).
- **Butuh Mengubah/Menambah Data Tetangga?** $\rightarrow$ *Event-Driven* (Buat *Event* di modul sumber, buat *Listener* di modul target).
