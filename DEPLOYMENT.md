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
