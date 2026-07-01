# Arsitektur Deployment & Manajemen Modul Wisma Amal

Dokumen ini menjelaskan alur *deployment* sistem (CI/CD), mekanisme aktivasi modul saat pengguna membeli lisensi baru (oleh Developer), dan cara pengguna (*User*) mengatur fitur modul secara mandiri melalui *Frontend*.

---

## 1. Alur Deployment (CI/CD)
Sistem Wisma Amal Gorontalo menggunakan arsitektur *containerized* (Docker) dan *Modular Monolith*. Proses *deployment* diotomatisasi menggunakan GitHub Actions.

### Diagram Deployment
```mermaid
sequenceDiagram
    participant Dev as Developer
    participant GitHub as GitHub Actions
    participant GHCR as GH Container Registry
    participant VPS as VPS Server (Production)

    Dev->>GitHub: Push ke branch `staging` / `main`
    GitHub->>GHCR: Build Docker Image & Push Image (Backend Web & App)
    GitHub->>VPS: SSH Login via Appleboy Action
    VPS->>VPS: Buat folder deployment & Copy docker-compose.yml
    VPS->>VPS: Generate default modules_statuses.json (jika belum ada)
    VPS->>GHCR: Pull Image terbaru
    VPS->>VPS: Eksekusi `docker compose up -d`
    VPS->>VPS: Run Migrations & Setup Permissions
    VPS-->>GitHub: Deployment Sukses
```

---

## 2. Alur Pembelian & Aktivasi Modul (Level Developer)
Sistem menggunakan `nwidart/laravel-modules` di mana `modules_statuses.json` bertindak sebagai *gatekeeper* utama. File ini menentukan modul mana saja yang kode sumbernya akan dimuat (*loaded*) ke dalam memori aplikasi Laravel.

### Kapan ini digunakan?
Saat pengguna (*tenant*/institusi) baru saja membeli modul tambahan. File ini diatur langsung oleh **Developer** (atau skrip otomatisasi *billing* di level infrastruktur).

### Diagram Aktivasi Modul
```mermaid
sequenceDiagram
    actor User as User (Client)
    participant Sales as Developer / Admin Sistem
    participant Server as VPS (modules_statuses.json)
    participant Docker as Docker Container

    User->>Sales: Membeli modul baru (misal: "Finance")
    Sales->>Server: SSH ke VPS & Buka `~/wismaamal-deploy/modules_statuses.json`
    Server->>Server: Ubah `"Finance": false` menjadi `"Finance": true`
    Sales->>Docker: Jalankan `docker exec wismaamal_backend_app php artisan optimize:clear`
    Sales->>Docker: Jalankan `docker restart wismaamal_backend_app`
    Docker->>Docker: Memuat routes, controllers, & service dari modul "Finance"
    Docker-->>User: Modul aktif dan kode backend tersedia
```

### Langkah Teknis Aktivasi di VPS:
1. **Edit File Konfigurasi:** Buka dan ubah status modul di `~/wismaamal-deploy/modules_statuses.json` menjadi `true`.
2. **Bersihkan Cache Laravel:** Agar framework tidak menggunakan cache lama yang menganggap modul belum aktif.
   ```bash
   docker exec wismaamal_backend_app php artisan optimize:clear
   ```
3. **Restart Container:** Agar aplikasi me-*load* ulang kerangka kerja dengan modul yang baru.
   ```bash
   docker restart wismaamal_backend_app
   ```

*(Catatan: Karena file json di-mount via volume di docker-compose, kita tidak perlu mem-build ulang image Docker).*

---

## 3. Alur Pengaturan Modul oleh User (Level Frontend / Soft-Toggle)
Setelah sebuah modul diaktifkan di level server, fitur dari modul tersebut sudah terintegrasi ke dalam sistem. Namun, pengguna (Admin) diberi kebebasan untuk **menyalakan/mematikan** (*soft-toggle*) fitur tersebut sesuai kebutuhan mereka.

Hal ini diatur melalui tabel `feature_toggles` di dalam database.

### Kapan ini digunakan?
Saat Admin institusi ingin menyembunyikan menu tertentu agar tidak membingungkan stafnya, meskipun secara lisensi mereka sudah membelinya.

### Diagram Manajemen Fitur oleh User
```mermaid
sequenceDiagram
    actor Admin as Admin Wisma (User)
    participant FE as Frontend UI (Menu Settings)
    participant API as Backend API
    participant DB as DB (Tabel `feature_toggles`)

    Admin->>FE: Masuk ke halaman Settings > Modul
    FE->>API: GET /api/settings/features (Ambil daftar modul aktif)
    API->>DB: Query `feature_toggles`
    DB-->>FE: Return data (misal: Finance=Aktif, Inventory=Aktif)
    
    Admin->>FE: Klik tombol "Non-aktifkan Inventory"
    FE->>API: POST /api/settings/features/inventory/toggle
    API->>DB: Update `is_active = false` di `feature_toggles`
    API-->>FE: Sukses update
    
    FE->>FE: Hilangkan menu "Inventory" dari Sidebar Navigation
    Note over Admin, FE: Jika staf mencoba akses paksa via URL langsung...
    FE->>API: Staf mengakses endpoint Inventory API
    API->>DB: Cek status `is_active`
    API-->>FE: 403 Forbidden (Fitur dinonaktifkan oleh Admin)
```

### Kesimpulan Pemisahan Peran:
1. **Level Kerangka Kerja (`modules_statuses.json`):** Berfungsi sebagai Hard-Toggle. Dikelola oleh pembuat sistem (Developer). Berdampak pada efisiensi memori karena modul yang dimatikan tidak akan membebani aplikasi (tidak me-load RAM).
2. **Level Aplikasi Database (`feature_toggles`):** Berfungsi sebagai Soft-Toggle. Dikelola oleh pengelola sistem (Admin Wisma). Berdampak pada UI/UX, di mana kode tetap berjalan di memori Laravel namun akses dari user dicegat oleh sistem otorisasi (Middleware).
