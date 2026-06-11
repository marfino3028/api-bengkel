# ---- BengkelKu API (Laravel 12) — image untuk Railway ----
FROM php:8.2-cli

# Ekstensi & dependency sistem
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring zip gd bcmath \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x docker/start.sh

ENV PORT=8000
EXPOSE 8000

CMD ["sh", "docker/start.sh"]
