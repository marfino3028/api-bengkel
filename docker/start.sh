#!/usr/bin/env sh
set -e

# Generate APP_KEY bila belum di-set (idempotent, hanya jika kosong)
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force || true
fi

# Migrasi database (aman dijalankan berulang)
php artisan migrate --force

# Seed data awal — idempotent (updateOrCreate). Hapus baris ini bila tak ingin auto-seed.
php artisan db:seed --force || true

# Symlink storage untuk file upload
php artisan storage:link || true

# Cache konfigurasi untuk performa (route tidak di-cache karena ada closure)
php artisan config:cache || true

# Jalankan server
exec php artisan serve --host 0.0.0.0 --port "${PORT:-8000}"
