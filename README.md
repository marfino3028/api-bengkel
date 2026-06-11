# BengkelKu API (Laravel 12)

Backend REST API untuk sistem **bengkel motor**: booking servis, katalog & penjualan sparepart, pembayaran/invoice, akun pelanggan, serta panel admin (CMS). Auth memakai **Laravel Sanctum** (Bearer token), database **MySQL** (produksi) / SQLite (lokal cepat).

Bagian dari ekosistem **BengkelKu**:

| Repo | Peran | Deploy |
|---|---|---|
| **api-bengkel** (repo ini) | REST API | **Railway** |
| [cms-bengkel](https://github.com/marfino3028/cms-bengkel) | Panel Admin (Nuxt) | Koyeb |
| [web-bengkel](https://github.com/marfino3028/web-bengkel) | Website publik (Nuxt) | Koyeb |
| [mobile-bengkel](https://github.com/marfino3028/mobile-bengkel) | Aplikasi mobile (Flutter) | Play Store / APK |

---

## ✨ Fitur API
- **Auth**: register, login, logout, profil (Sanctum token) — role `admin` & `customer`.
- **Katalog**: kategori, produk (sparepart) dengan filter/pencarian/sort + pagination, layanan servis, banner, profil bengkel.
- **Booking servis**: buat booking + pilih layanan, riwayat, batal.
- **Order sparepart**: keranjang → checkout (validasi & potong stok otomatis), riwayat, batal (stok dikembalikan).
- **Admin**: dashboard statistik, CRUD produk/kategori/layanan/banner, kelola booking (status, tambah sparepart/jasa, pembayaran), kelola order, data pelanggan, pengaturan bengkel, upload gambar.

Dokumentasi kontrak lengkap: lihat [`API_CONTRACT.md`](API_CONTRACT.md).

---

## 🚀 Menjalankan Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate

# Cara cepat: pakai SQLite (tanpa server DB) -> set di .env: DB_CONNECTION=sqlite
touch database/database.sqlite

php artisan migrate --seed
php artisan storage:link
php artisan serve     # http://localhost:8000
```

### Akun hasil seed
| Role | Email | Password |
|---|---|---|
| Admin | `admin@bengkelku.com` | `password` |
| Pelanggan | `budi@mail.com` | `password` |

Cek cepat: `GET http://localhost:8000/api/health` → `{"status":"ok"}`.

---

## ☁️ Deploy ke Railway (+ MySQL)

> Railway khusus untuk API ini. Frontend (Nuxt) deploy di Koyeb.

1. **Buat project**: [railway.app](https://railway.app) → **New Project** → **Deploy from GitHub repo** → pilih `marfino3028/api-bengkel`. Railway mendeteksi `Dockerfile` & `railway.json` otomatis.
2. **Tambah MySQL**: di project → **New** → **Database** → **Add MySQL**.
3. **Set Variables** (tab *Variables* pada service API). Gunakan *reference variable* ke service MySQL:
   ```
   APP_NAME=BengkelKu API
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=            # kosongkan; otomatis di-generate saat start (atau isi hasil `php artisan key:generate --show`)
   APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}

   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

   FRONTEND_URLS=*       # atau daftar origin Nuxt dipisah koma
   ```
4. **Generate Domain**: tab *Settings* → *Networking* → **Generate Domain**. Dapat URL seperti `https://api-bengkel-production.up.railway.app`.
5. Saat deploy, container otomatis: `migrate --force` → `db:seed` (idempotent) → `storage:link` → `config:cache` → jalan di `0.0.0.0:$PORT`.
6. Tes: buka `https://<domain-railway>/api/health`.

> **Catatan penyimpanan gambar**: filesystem Railway *ephemeral* (file upload hilang saat redeploy). Seed memakai URL gambar eksternal sehingga katalog tetap tampil. Untuk gambar upload permanen, gunakan S3/Cloudinary (`FILESYSTEM_DISK`) atau cukup isi field gambar produk dengan URL.

---

## 🌐 Custom Domain via Cloudflare — `apibengkel.hamztech.my.id`

API ini dipasang di subdomain **`apibengkel.hamztech.my.id`** (Railway). Prasyarat: domain `hamztech.my.id` sudah aktif di Cloudflare.

**Langkah 1 — Railway:** service API → **Settings** → **Networking** → **Custom Domain** → ketik `apibengkel.hamztech.my.id` → **Add Domain**. Salin target CNAME yang ditampilkan (mis. `abcd1234.up.railway.app`).

**Langkah 2 — Cloudflare DNS** (`hamztech.my.id` → DNS → Add record):

| Type | Name | Target | Proxy | TTL |
|---|---|---|---|---|
| `CNAME` | `apibengkel` | `abcd1234.up.railway.app` *(dari Railway)* | **DNS only** (abu-abu) | Auto |

> Wajib **DNS only** dulu agar Railway bisa menerbitkan SSL. Setelah aktif boleh diubah ke **Proxied** + SSL/TLS mode **Full (strict)**.

**Langkah 3 — Tes:** `https://apibengkel.hamztech.my.id/api/health` → `{"status":"ok"}`.

**Langkah 4 — Variables Railway:**
```
APP_URL=https://apibengkel.hamztech.my.id
FRONTEND_URLS=https://webbengkel.hamztech.my.id,https://cmsbengkel.hamztech.my.id
```

### 🗺️ Peta domain produksi
| Subdomain | Tujuan | Platform |
|---|---|---|
| `apibengkel.hamztech.my.id` | API Laravel (repo ini) | **Railway** |
| `webbengkel.hamztech.my.id` | Website publik | Koyeb |
| `cmsbengkel.hamztech.my.id` | Panel admin | Koyeb |

Konfigurasi penghubung: web & CMS set `NUXT_PUBLIC_API_BASE=https://apibengkel.hamztech.my.id/api`; Midtrans Notification URL = `https://apibengkel.hamztech.my.id/api/payments/notification`; mobile build dengan `--dart-define=API_BASE_URL=https://apibengkel.hamztech.my.id/api`.

---

## 🔑 Ringkasan Endpoint
Prefix semua: `/api` (detail di [`API_CONTRACT.md`](API_CONTRACT.md)).
- Publik: `POST /auth/login`, `POST /auth/register`, `GET /categories|products|services|banners|settings`.
- Pelanggan (Bearer): `GET/POST /bookings`, `GET/POST /orders`, `GET /auth/me`, `PUT /auth/profile`.
- Admin (Bearer, role admin): `GET /admin/dashboard`, `apiResource` products/categories/services/banners, kelola `/admin/bookings` & `/admin/orders`, `/admin/customers`, `/admin/settings`, `POST /admin/upload`.

## 💳 Pembayaran Online (Midtrans)

Mendukung **Midtrans Snap** untuk order & booking. Tanpa konfigurasi, endpoint pembayaran mengembalikan 422 yang ramah (fallback ke pembayaran manual) sehingga app tetap jalan.

1. Daftar di [dashboard.midtrans.com](https://dashboard.midtrans.com) → ambil **Server Key** & **Client Key** (Sandbox dulu).
2. Set env: `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION=false`.
3. Set **Payment Notification URL** di Midtrans → `https://apibengkel.hamztech.my.id/api/payments/notification`.

Endpoint: `POST /api/orders/{code}/pay` & `POST /api/bookings/{code}/pay` (auth) → `{ snap_token, redirect_url, client_key }`. Webhook `POST /api/payments/notification` (publik, diverifikasi signature SHA-512) → otomatis menandai lunas + memicu notifikasi WhatsApp.

## 🔔 Notifikasi WhatsApp

Otomatis saat: booking dibuat, order dibuat, status berubah, pembayaran lunas. Pakai gateway **Fonnte** (atau kompatibel). Tanpa token → di-skip diam-diam (app tetap normal).

- Set env: `WHATSAPP_TOKEN` (dari [fonnte.com](https://fonnte.com)), opsional `WHATSAPP_ADMIN_NUMBER` (notifikasi internal admin).

## 🤖 CI/CD (GitHub Actions)
- `.github/workflows/ci.yml` — test PHP otomatis tiap push/PR (PHP 8.2 + SQLite).
- `.github/workflows/deploy.yml` — deploy ke Railway (opsional; set secret `RAILWAY_TOKEN` & `RAILWAY_SERVICE`). Alternatif termudah: aktifkan auto-deploy native Railway dari GitHub.

## 🛠️ Stack
Laravel 12 · PHP 8.2 · Sanctum · MySQL · Midtrans · API Resources.
