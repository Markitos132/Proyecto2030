# ═══════════════════════════════════════════════════════════
#  BioNEA Organiks — imagen para Render
#
#  Render no tiene runtime nativo de PHP, así que el servicio se
#  despliega como Docker.
#
#  Se usa FrankenPHP (Caddy + PHP embebido) en lugar de nginx +
#  php-fpm + supervisor: un solo proceso, una sola configuración,
#  y concurrencia real. `php artisan serve` no sirve en producción
#  porque atiende una petición por vez.
#
#  No se compila nada con Vite: ninguna vista usa @vite y todo el
#  CSS se sirve estático desde public/css.
# ═══════════════════════════════════════════════════════════

FROM dunglas/frankenphp:php8.3

# pdo_pgsql y pgsql son imprescindibles para hablar con Supabase.
# opcache e intl mejoran el rendimiento y el formato de fechas.
RUN install-php-extensions \
        pdo_pgsql \
        pgsql \
        opcache \
        intl \
        zip

# La imagen base le asigna cap_net_bind_service al binario de FrankenPHP
# para poder escuchar en el puerto 80. Render ejecuta los contenedores
# con no-new-privileges, y en ese modo el kernel rechaza con EPERM el
# exec de cualquier binario con file capabilities: el arranque moria con
# "Operation not permitted" (status 126).
#
# No hacen falta: Render asigna un puerto alto (10000), y por encima de
# 1024 no se requiere ninguna capacidad especial.
#
# Se copia el binario en lugar de usar setcap -r porque `setcap` no
# siempre esta instalado en la imagen. `cp` no preserva los atributos
# extendidos, asi que la copia queda sin capacidades. El getcap final
# corta el build si por algun motivo sobrevivieran.
RUN cp /usr/local/bin/frankenphp /tmp/frankenphp \
    && rm -f /usr/local/bin/frankenphp \
    && mv /tmp/frankenphp /usr/local/bin/frankenphp \
    && chmod 0755 /usr/local/bin/frankenphp \
    && if command -v getcap >/dev/null 2>&1; then \
           echo "capacidades restantes: [$(getcap /usr/local/bin/frankenphp)]"; \
           test -z "$(getcap /usr/local/bin/frankenphp)" \
               || { echo "ERROR: el binario conserva file capabilities"; exit 1; }; \
       else \
           echo "getcap no disponible; se confia en que cp descarto los xattr"; \
       fi \
    && frankenphp version

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Instalar dependencias antes de copiar el código: mientras
# composer.json y composer.lock no cambien, Docker reutiliza esta capa.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

COPY . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# El usuario del servidor tiene que poder escribir logs, sesiones,
# cache de archivos y vistas compiladas.
RUN mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Render inyecta PORT en tiempo de ejecución; el entrypoint lo lee.
EXPOSE 8080

ENTRYPOINT ["entrypoint"]
