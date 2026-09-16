#!/usr/bin/env bash
# ============================================================
# Dentamor - despliegue en CONTABO (solo el contenedor dentamor_app)
# ============================================================
# - Detecta la red compartida (nginx/mysql) y levanta la app.
# - NUNCA reinicia ni recrea jemigra_web (nginx): el vhost va al
#   FINAL y solo con nginx -t + reload (pasos que se imprimen).
# - Usa bash:  bash deploy.sh
# ============================================================
set -euo pipefail

PROJECT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT"

echo "==> 0) docker compose v2"
docker compose version

echo ""
echo "==> 1) Detectando red del nginx (jemigra_web)..."
WEB_NET="$(DENTAMOR_NET=${DENTAMOR_NET:-} docker inspect jemigra_web --format '{{range $k,$v := .NetworkSettings.Networks}}{{$k}} {{end}}' 2>/dev/null | cut -d' ' -f1 || true)"
WEB_NET="${DENTAMOR_NET:-$WEB_NET}"
if [ -z "$WEB_NET" ]; then
    echo "    [ERROR] No detecte la red del nginx. Pasa DENTAMOR_NET=<red>."
    exit 1
fi
echo "    DENTAMOR_NET=$WEB_NET"

echo ""
echo "==> 2) Detectando red del mysql centralizado (c6600299f82a_jemigra_db)..."
DB_NET="$(docker inspect c6600299f82a_jemigra_db --format '{{range $k,$v := .NetworkSettings.Networks}}{{$k}} {{end}}' 2>/dev/null | cut -d' ' -f1 || true)"
echo "    Red del mysql: ${DB_NET:-desconocida}"

echo ""
echo "==> 3) .env (raiz del repo). Sin .env no se puede levantar:"
if [ ! -f .env ]; then
    cp deploy/.env.production.example .env
    echo "    .env creado desde la plantilla. EDITALO ANTES de continuar:"
    echo "      - APP_KEY (vacia): se genera en el step 6)"
    echo "      - DB_HOST=jemigra_db  (correcto por defecto)"
    echo "      - DB_DATABASE=dentamor  DB_USERNAME=dentamor"
    echo "      - DB_PASSWORD=CLAVE_QUE_CREASTE en el mysql centralizado"
    echo "      - APP_URL=https://dentamor.jemigra.com"
    exit 1
fi
echo "    Ya existe .env"

echo ""
echo "==> 4) Build + up en la red [$WEB_NET]..."
export DENTAMOR_NET="$WEB_NET"
docker compose -f deploy/docker-compose.yml up -d --build
echo "    dentamor_app arriba."

echo ""
echo "==> 5) Red de BD (solo si difiere de la red del web)..."
if [ -n "$DB_NET" ] && [ "$DB_NET" != "$WEB_NET" ]; then
    echo "    Conectando dentamor_app tambien a $DB_NET ..."
    docker network connect "$DB_NET" dentamor_app 2>/dev/null || true
else
    echo "    Misma red ($WEB_NET): no hace falta conectar."
fi

echo ""
echo "==> 6) Verificacion y primer arranque (el entrypoint ya migra y cachea):"
echo "    docker logs dentamor_app"
echo "    docker exec dentamor_app php artisan key:generate --force   (si APP_KEY quedo vacia)"
echo "    docker exec dentamor_app php artisan db:seed --force        (opcional: Company inicial)"
echo ""
echo "    nginx SOLO al final y con doble chequeo (jamas restart):"
echo "    docker cp deploy/nginx/dentamor.conf jemigra_web:/etc/nginx/conf.d/dentamor.conf"
echo "    docker exec jemigra_web nginx -t            # debe decir 'syntax is ok'"
echo "    docker exec jemigra_web nginx -s reload     # SOLO si el test paso"
echo ""
echo "    Para revertir el vhost si algo falla:"
echo "    docker exec jemigra_web rm /etc/nginx/conf.d/dentamor.conf && docker exec jemigra_web nginx -s reload"