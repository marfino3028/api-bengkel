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

## 🌐 Custom Domain via Cloudflare (subdomain `api`)

Ekosistem butuh **3 subdomain** (lihat juga README web & cms). Untuk API gunakan **`api`**:

1. **Railway** → service API → *Settings* → *Networking* → **Custom Domain** → ketik `api.domainmu.com`. Railway menampilkan target CNAME, mis. `xxxx.up.railway.app`.
2. **Cloudflare** → domainmu → **DNS** → **Add record**:
   - Type `CNAME` · Name `api` · Target `xxxx.up.railway.app` (dari Railway)
   - Proxy status: **DNS only** (abu-abu) sampai SSL Railway terbit, lalu boleh **Proxied** (oranye).
3. Tunggu Railway verifikasi domain (SSL otomatis). API di `https://api.domainmu.com`.
4. Set `APP_URL=https://api.domainmu.com`, dan di frontend set `NUXT_PUBLIC_API_BASE=https://api.domainmu.com/api`.

---

## 🔑 Ringkasan Endpoint
Prefix semua: `/api` (detail di [`API_CONTRACT.md`](API_CONTRACT.md)).
- Publik: `POST /auth/login`, `POST /auth/register`, `GET /categories|products|services|banners|settings`.
- Pelanggan (Bearer): `GET/POST /bookings`, `GET/POST /orders`, `GET /auth/me`, `PUT /auth/profile`.
- Admin (Bearer, role admin): `GET /admin/dashboard`, `apiResource` products/categories/services/banners, kelola `/admin/bookings` & `/admin/orders`, `/admin/customers`, `/admin/settings`, `POST /admin/upload`.

## 🛠️ Stack
Laravel 12 · PHP 8.2 · Sanctum · MySQL · API Resources.
