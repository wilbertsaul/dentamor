# ============================================================
# Dentamor - imagen de produccion (PHP-FPM) para CONTABO
# Se construye desde la raiz del repo:
#   docker compose -f deploy/docker-compose.yml up -d --build
#
# .env NO se copia a la imagen (dockerignore): se monta en runtime
# como volumen (ver docker-compose.yml), asi los cambios no exigen rebuild.
# ============================================================

# ---------- Stage 1: assets front-end (Vite) ----------
FROM node:20-alpine AS frontend
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---------- Stage 2: dependencias PHP (sin dev) ----------
FROM composer:2 AS vendor
WORKDIR /build
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# ---------- Stage 3: runtime PHP-FPM ----------
FROM php:8.2-fpm-alpine AS runtime
WORKDIR /var/www/html

# Librerias del sistema de runtime y deps de compilacion
RUN apk add --no-cache \
        icu-libs \
        libzip \
        libpng \
        libjpeg-turbo \
        freetype \
        libxml2 \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libxml2-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath \
        mbstring \
        intl \
        zip \
        soap \
        gd \
        opcache \
    && apk del .build-deps

# Artefactos de las stages previas
COPY --from=frontend /build/public/build ./public/build
COPY --from=vendor  /build/vendor ./vendor
COPY --from=vendor  /build/bootstrap/cache ./bootstrap/cache

# Codigo fuente (sin vendor/node_modules/.env/storage: ver .dockerignore)
COPY . .

# Entrypoint + estructura minima de storage
COPY deploy/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]