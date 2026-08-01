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
