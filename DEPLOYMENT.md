# Panduan Dokumentasi Deployment: Wisma Amal Gorontalo

Dokumentasi ini menjelaskan cara kerja sistem **CI/CD** otomatis menggunakan **Docker**, **GitHub Actions**, dan **Tailscale** untuk keamanan server.

---

## 🏗️ Arsitektur Logis
1.  **GitHub Actions**: Membangun (*build*) Docker Image dan push ke GitHub Container Registry (GHCR).
2.  **Tailscale**: Membuat lorong aman (*VPN*) agar GitHub bisa masuk ke VPS tanpa membuka port publik.
3.  **Docker Compose**: Menjalankan aplikasi di VPS dengan satu perintah.
4.  **Nginx**: Menangani routing antara Frontend (Flutter) dan Backend (Laravel).

---

## 🛠️ Persiapan Awal di Server (VPS)
Pastikan Anda sudah menjalankan perintah ini di server:
```bash
# 1. Install Docker
sudo apt update && sudo apt install -y docker.io docker-compose-v2

# 2. Buat Struktur Folder
mkdir -p ~/wisma-amal-deploy/docker
cd ~/wisma-amal-deploy

# 3. Buat File .env (PENTING!)
nano .env # Isi dengan APP_KEY, DB_PASSWORD, dll.
```

---

## 🔑 Konfigurasi GitHub Secrets
Anda harus mendaftarkan rahasia berikut di menu **Settings > Secrets and variables > Actions**:

| Nama Secret | Deskripsi | Sumber |
| :--- | :--- | :--- |
| `SSH_PRIVATE_KEY` | Kunci Privat SSH pendaftar | File `~/id_rsa_vps` di laptop |
| `REMOTE_USER` | Username login VPS | Biasanya `root` atau `ubuntu` |
| `TAILSCALE_AUTHKEY` | Kunci akses Tailscale | Menu **Settings > Keys** di Tailscale Admin |
| `DB_PASSWORD` | Password database app | Bebas (Buat sendiri) |
| `DB_ROOT_PASSWORD` | Password root database | Bebas (Buat sendiri) |

---

## 🔗 Konfigurasi Tailscale
1.  Buka **[Tailscale Admin](https://login.tailscale.com/admin/settings/keys)**.
2.  Buat **Auth Key** baru:
    *   **Reusable**: Yes (Bisa dipakai berulang).
    *   **Ephemeral**: Yes (Node GitHub otomatis hilang setelah selesai).
3.  Pastikan VPS Anda terdaftar di Tailscale dan memiliki IP `100.125.64.93`.

---

## 🚀 Cara Melakukan Update (Deployment)
Cukup lakukan push ke branch `main`:
```bash
git add .
git commit -m "Update aplikasi"
git push origin main
```
GitHub Actions akan secara otomatis:
1.  Membangun image baru.
2.  Menghubungkan ke Tailscale.
3.  Masuk ke VPS via SSH.
4.  Menjalankan `docker compose pull` dan `docker compose up -d`.
5.  Menjalankan `php artisan migrate` secara otomatis.

---

## ✅ Checklist Konfigurasi Wajib Saat Deploy

> **Cara pakai:** Setiap kali ada fitur baru yang butuh setup manual di server (cron, env, seeder, dsb.), tambahkan di section yang relevan di bawah. Sertakan tanggal penambahan dan konteks singkat agar jelas kapan dan kenapa itu diperlukan.

---

### 🕐 Cron / Laravel Scheduler

Jadikan Laravel Scheduler berjalan di server. Tanpa ini, fitur expiry otomatis tidak akan bekerja.

```bash
# Tambahkan ke crontab server (cukup sekali):
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1

# Untuk verifikasi scheduler sudah berjalan:
php artisan schedule:list
```

| Command | Jadwal | Ditambahkan | Fungsi |
|---|---|---|---|
| `schedule:expire-pending` | Setiap menit | 2026-06-14 | Batalkan jadwal sewa PENDING yang melebihi 15 menit tanpa ada pembayaran. Membebaskan kamar yang dibooking tapi tidak dilanjutkan bayar. |
| `notification:lease-reminders` | Setiap hari jam 08:00 | — | Kirim pengingat WhatsApp ke penghuni yang kontrak sewanya hampir habis. |

---

### 🔑 Environment Variables Tambahan

Selain variabel di `.env.example`, pastikan variabel berikut diisi sebelum deploy:

| Variable | Contoh Nilai | Ditambahkan | Keterangan |
|---|---|---|---|
| `MIDTRANS_SERVER_KEY` | `SB-Mid-server-xxx` | — | Server key Midtrans. Gunakan key **production** (bukan sandbox) saat live. |
| `MIDTRANS_CLIENT_KEY` | `SB-Mid-client-xxx` | — | Client key Midtrans untuk frontend. |
| `MIDTRANS_IS_PRODUCTION` | `true` | — | Set `false` untuk sandbox/testing, `true` untuk production. |
| `MIDTRANS_NOTIFICATION_URL` | `https://domain.com/api/finance/payments/midtrans/notification` | 2026-06-14 | URL webhook yang didaftarkan di dashboard Midtrans. Midtrans akan kirim notifikasi expire/settlement ke sini. Wajib dapat diakses publik (bukan localhost). |
| `FONNTE_TOKEN` | `abc123` | — | Token API Fonnte untuk notifikasi WhatsApp. |

---

### 🌱 Database Seeder

Jalankan seeder berikut setelah `migrate:fresh` di production (sekali saja):

```bash
php artisan db:seed --class=Modules\\Setting\\Database\\Seeders\\SettingDatabaseSeeder
```

| Seeder | Ditambahkan | Isi |
|---|---|---|
| `SettingDatabaseSeeder` | — | Nilai default konfigurasi: nama wisma, toggle fitur Midtrans/WhatsApp, info rekening bank (`bank_name`, `bank_account`, `bank_holder`). Wajib ada agar halaman keuangan member tidak kosong. |

---

### 🔔 Konfigurasi Midtrans Dashboard

Hal-hal yang harus dikonfigurasi di **[dashboard Midtrans](https://dashboard.midtrans.com)**:

1. **Payment Notification URL** → isi dengan URL webhook: `https://domain.com/api/finance/payments/midtrans/notification`
2. **Finish / Unfinish / Error Redirect URL** → arahkan ke halaman yang sesuai di frontend
3. **Enable payment methods** yang ingin diaktifkan (QRIS, VA, dll.)

> Catatan (2026-06-14): Snap token dikonfigurasi dengan expiry 15 menit. Artinya Midtrans akan otomatis kirim webhook `expire` setelah 15 menit jika user tidak menyelesaikan pembayaran. Pastikan webhook URL terdaftar dan bisa diakses.

---

## ❓ Troubleshooting (Masalah Umum)

### 1. Error: `ssh: no key found`
*   **Penyebab**: Format `SSH_PRIVATE_KEY` di GitHub salah atau nama secret tidak sesuai.
*   **Solusi**: Pastikan copy seluruh isi file mulai baris `-----BEGIN RSA PRIVATE KEY-----` sampai baris terakhir.

### 2. Error: `Permission denied` saat SCP
*   **Penyebab**: Folder di VPS dimiliki oleh `root`, bukan user Anda.
*   **Solusi**: Jalankan `sudo chown -R $USER:$USER ~/wisma-amal-deploy` di VPS.

### 3. Database Tidak Connect
*   **Penyebab**: Password di `.env` server berbeda dengan yang di GitHub Secrets.
*   **Solusi**: Pastikan `DB_PASSWORD` di `.env` server sama dengan yang Anda set di GitHub.
