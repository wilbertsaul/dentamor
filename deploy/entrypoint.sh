#!/bin/sh
# Dentamor (IziPay) - entrypoint del contenedor app (dentamor_app)
# Corre como root para poder ajustar permisos del volumen named dentamor_storage.
set -e

HTML=/var/www/html

# 1) Estructura minima de storage (volumen named arranca VACIO el 1er boot)
mkdir -p \
    "$HTML/storage/app/sunat/cache" \
    "$HTML/storage/app/sunat/xml" \
    "$HTML/storage/app/sunat/cdr" \
    "$HTML/storage/app/sunat/pdf" \
    "$HTML/storage/app/sunat/certificates" \
    "$HTML/storage/app/sunat/certificates/test" \
    "$HTML/storage/app/certificates" \
    "$HTML/storage/app/public" \
    "$HTML/storage/framework/cache/data" \
    "$HTML/storage/framework/sessions" \
    "$HTML/storage/framework/views" \
    "$HTML/storage/logs"

# 2) Permisos: storage y bootstrap/cache deben ser W por el usuario php (www-data)
chown -R www-data:www-data "$HTML/storage" "$HTML/bootstrap/cache"

# 2b) APP_KEY: generar si falta (el .env viene montado desde el host).
#     Se genera ANTES de config:cache para no cachear la key vacia.
if [ -f "$HTML/.env" ] && ! grep -q '^APP_KEY=base64' "$HTML/.env"; then
    echo "[dentamor] Generando APP_KEY..."
    php "$HTML/artisan" key:generate --force --no-interaction
fi

# 3) Storage central en produccion: comprobar si la foto fshowale logo/empresa
#    Dentamor guarda solo el storage/app real; el symlink public/storage no
#    se usa aqui (nginx sirve los PDF/XML por ruta protegida del controlador).

# 4) Migraciones automaticas (idempotente). La DB se alcanza por la red docker
#    (hostname del contenedor mysql), NO por 127.0.0.1.
echo "[dentamor] Migrando la base de datos..."
php "$HTML/artisan" migrate --force --no-interaction

# 5) Cache de produccion (mejor performance, necesario con APP_ENV=production)
php "$HTML/artisan" config:cache --ansi --no-interaction 2>/dev/null || true
php "$HTML/artisan" route:cache --ansi --no-interaction 2>/dev/null || true
php "$HTML/artisan" view:cache --ansi --no-interaction 2>/dev/null || true

# 6) Ejecutar el comando indicado (default: php-fpm). Como www-data.
exec su -s /bin/sh www-data -c "$@"