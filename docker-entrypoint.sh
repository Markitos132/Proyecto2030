#!/bin/sh
# ═══════════════════════════════════════════════════════════
#  Arranque del contenedor en Render.
# ═══════════════════════════════════════════════════════════
set -e

# Render asigna el puerto por variable de entorno. FrankenPHP lo lee
# desde SERVER_NAME; sin host delante, escucha en todas las interfaces.
export SERVER_NAME=":${PORT:-8080}"

echo "→ BioNEA Organiks arrancando en el puerto ${PORT:-8080}"

# Los directorios de storage no viajan en git (solo sus .gitignore),
# y el disco de Render es efímero: hay que recrearlos en cada arranque.
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Las migraciones no corren solas por defecto. Aplicarlas en cada deploy
# es cómodo hasta el día que un despliegue automático altera la base de
# producción sin que nadie lo pida. Se activa poniendo
# RUN_MIGRATIONS=true en el entorno del servicio.
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "→ Aplicando migraciones"
    php artisan migrate --force --no-interaction
fi

# Compilar configuración, rutas y vistas. Evita releer y reinterpretar
# decenas de archivos en cada petición.
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "→ Listo"

exec frankenphp run --config /etc/caddy/Caddyfile
