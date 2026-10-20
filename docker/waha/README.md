# WhatsApp (WAHA) en Contabo

Dentamor envía boletas, facturas y notas de crédito aceptadas por SUNAT por WhatsApp, con el **PDF adjunto**, usando [WAHA](https://waha.devlike.pro/) (devlike.pro) en lugar de la Meta Cloud API.

## Arquitectura

- WAHA corre en **Docker, en el mismo servidor Contabo** que Laravel, en la red `jemigra_jemigra_net` (la misma de `dentamor_app` y `jemigra_web`).
- El puerto solo se publica en `127.0.0.1:3000` del host (no expuesto a internet; sirve para sacar el QR por SSH).
- Laravel corre dentro de un contenedor, así que se conecta por la red Docker: `WHATSAPP_BASE_URL=http://dentamor_waha:3000` (no `127.0.0.1`) y `WHATSAPP_API_KEY`.
- El envío usa `POST /api/sendFile` con el PDF en base64 y un caption (cliente, tipo/número, total). No se usa plantilla de Meta ni enlace firmado en el mensaje.

## Instalación (una sola vez)

1. Genera una API key:

   ```bash
   openssl rand -hex 32
   ```

2. Crea `docker/waha/.env` en el servidor (no va a git):

   ```bash
   WAHA_API_KEY=<la clave generada>
   ```

3. Levanta WAHA:

   ```bash
   docker compose -f docker/waha/docker-compose.yml up -d
   ```

4. En el `.env` de Laravel, asegúrate de tener:

   ```env
   WHATSAPP_BASE_URL=http://dentamor_waha:3000
   WHATSAPP_API_KEY=<la misma clave>
   ```

   Luego limpia la config cacheada: `docker exec dentamor_app php artisan config:clear`

5. En la app: **Empresa → Configuración → WhatsApp (WAHA)**

   - Botón **Consultar sesión**: debe responder.
   - Si el estado es `SCAN_QR`, escanea el QR desde el teléfono
     (WhatsApp → Dispositivos vinculados → Vincular dispositivo).
     El QR se obtiene con: `curl -H "X-Api-Key: $KEY" http://127.0.0.1:3000/api/sessions` (campo `qr`).
   - Cuando el estado sea `WORKING`, activa **Envío automático** y guarda.

## Operación

| Acción | Comando |
| --- | --- |
| Estado | `docker ps --filter name=dentamor_waha` |
| Logs | `docker logs -f dentamor_waha` |
| Reiniciar | `docker compose -f docker/waha/docker-compose.yml restart` |
| Parar | `docker compose -f docker/waha/docker-compose.yml down` |
| Quitar sesión huérfana | `curl -X DELETE -H "X-Api-Key: $KEY" http://127.0.0.1:3000/api/sessions/default` |

- La sesión se persiste en `docker/waha/sessions/` (volumen). Si el contenedor se recrea, la sesión sigue.
- Si WhatsApp cierra la sesión (cambio de teléfono, ban, etc.), el estado pasa a `SCAN_QR` o `STOPPED`: vuelve a escanear el QR.
- El botón **Reconectar sesión** en la UI llama a `POST /api/sessions/{name}/start`.

## Recursos

- Imagen `devlikeapro/waha:gows`: ~770 MB comprimido, ~300-500 MB RAM (recomendada).
- Mínimo recomendado del servidor: 2 vCPU / 2-4 GB RAM / 20 GB de disco extra.

## Riesgos conocidos

- No es la API oficial de Meta: una cuenta de WhatsApp puede ser **baneada** si se envía spam o se usa la misma cuenta para otras cosas. Usa un **número dedicado exclusivo** del sistema.
- El número debe tener WhatsApp activo (verificación por SMS/llamada) una sola vez; después solo se mantiene la sesión.
