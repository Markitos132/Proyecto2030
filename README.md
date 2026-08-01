# BioNEA Organiks

Sistema de monitoreo térmico para investigación en ecofisiología de lagartijas.
Un dispositivo ESP32 mide la temperatura de individuos en campo y la envía a una
API; el panel web permite gestionar ejemplares, dispositivos y sesiones de
medición, y consultar el historial.

Desarrollado junto a investigadores del IIGHI–CONICET.

## Stack

- **Laravel 13** sobre PHP 8.3
- **PostgreSQL** alojado en Supabase
- **Blade** para las vistas, CSS propio servido desde `public/css`
- **Chart.js** por CDN para los gráficos de temperatura
- Desplegado en **Render** como servicio Docker (FrankenPHP)

## Puesta en marcha

Requisitos: PHP 8.3 con las extensiones `pdo_pgsql` y `pgsql`, y Composer.
En Windows, [Laravel Herd](https://herd.laravel.com/windows) trae todo.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Completá `DB_URL` en el `.env` con la cadena de conexión de Supabase:

```
DB_URL=postgresql://usuario:clave@host.pooler.supabase.com:5432/postgres?sslmode=require
```

> **Usá el puerto 5432, no el 6543.** El 6543 es el pooler en modo transacción,
> que rompe los prepared statements de PDO con errores intermitentes bajo carga.
> El 5432 del mismo host es el pooler de sesión, compatible con Laravel.

Después:

```bash
php artisan migrate
php artisan serve
```

Las migraciones están protegidas con `Schema::hasTable()`: correrlas contra una
base que ya tiene los datos es una operación inocua, y contra una base vacía
construyen el esquema completo.

Para crear el primer usuario, `php artisan db:seed` deja uno en
`admin@bionea.local`. **Cambiale la clave antes de usarlo.**

### Nota sobre el servidor de desarrollo

`php artisan serve` atiende **una petición por vez**. Alcanza para navegar el
panel, pero si además corrés el simulador del ESP32 se forma una cola y todo se
vuelve lentísimo. Para ese caso conviene servir el proyecto con Herd
(`herd link bionea`), que usa varios workers.

## Arquitectura

### Ingesta del ESP32

El dispositivo hace `POST /bionea/guardar` con un JSON:

```json
{
  "session_id": "49029357",
  "tipo": "medicion",
  "fecha": "31/07/2026",
  "hora": "23:04:32",
  "individuo": "LAG-001",
  "especie": "Liolaemus chacoensis",
  "temperatura": 28.4,
  "temp_min": 20,
  "temp_max": 38,
  "alerta": "OK"
}
```

`tipo` puede ser `medicion` o `fin_sesion`. El campo `session_id` lo genera el
ESP32 con `millis()` y se guarda en `sesiones.sesion_externa`, lo que permite
retomar una sesión aunque el servidor se reinicie.

Si el individuo no está cargado, se crea automáticamente a partir de su código
para no perder la medición; después se completa la ficha a mano.

El endpoint exige la cabecera `X-API-Key` con el valor de `BIONEA_API_KEY`
(también acepta `Authorization: Bearer`). Dos escapes deliberados:

- **Si `BIONEA_API_KEY` está vacía, el endpoint acepta todo.** Es el
  comportamiento histórico, y permite desplegar el cambio sin cortar la ingesta
  mientras el firmware todavía no manda la clave. El orden correcto es:
  desplegar, grabar la clave en el ESP32, y recién entonces definir la variable.
- **Un usuario autenticado pasa sin clave.** Es lo que mantiene funcionando el
  simulador de `/simulador` sin incrustar la clave en una página web.

`GET /bionea/sesion?mac=...` es lo que el dispositivo consulta al arrancar y
cada 30 segundos: devuelve la sesión que le fue asignada desde el panel, o `204`
sin cuerpo si no tiene ninguna. Cada consulta cuenta además como señal de vida,
así que un equipo encendido figura online aunque no esté midiendo.

`GET /bionea/health` responde el estado de la app y de la base, e incluye
`ingesta_protegida` para poder verificar de un vistazo si la clave quedó puesta.

### Probar sin el hardware

`herramientas/simular-esp32.ps1` se comporta igual que el firmware: pregunta por
su sesión, la ejecuta y avisa cuando termina.

```powershell
.\herramientas\simular-esp32.ps1 -Clave "la-api-key" -Acelerar
```

Con `-Acelerar` los minutos se interpretan como segundos, así una sesión de 30
minutos se completa en medio minuto.

Antes de usarlo hay que dar de alta un dispositivo en el panel con la MAC que el
script informa (por defecto `A0:B1:C2:D3:E4:F5`), igual que habría que hacer con
un ESP32 real.

Corre fuera del navegador y sin sesión iniciada, así que atraviesa la misma
autenticación por `X-API-Key` que el dispositivo. El simulador anterior, que
vivía en `/simulador`, se retiró: corría autenticado —con lo que no ejercitaba la
clave— y seguía el flujo viejo en el que el dispositivo inventaba su propia
sesión en vez de recibirla del panel.

### Zona horaria

Las columnas de fecha son `timestamp without time zone` y guardan la hora local
que manda el ESP32. Por eso `APP_TIMEZONE` está en `America/Argentina/Buenos_Aires`:
si la app corriera en UTC, los dispositivos aparecerían como desconectados y los
promedios de temperatura saldrían vacíos.

### Sesiones huérfanas

El ESP32 avisa el fin de una sesión con un `fin_sesion`. Ese aviso no llega si el
equipo se queda sin batería o pierde el WiFi, y la sesión quedaría abierta para
siempre. `App\Services\CierreDeSesiones` las cierra cuando dejan de recibir
mediciones durante tres veces su intervalo configurado (mínimo 15 minutos), y se
dispara tanto desde una tarea programada como al cargar el panel.

### Refresco del panel

El dashboard consulta `GET /panel/estado` desde `public/js/panel-vivo.js`.
Un solo temporizador por pestaña, que se detiene cuando la pestaña no está
visible y espacia los reintentos ante fallos.

El ritmo lo decide el servidor y viene en el campo `proximo_en`: **3 segundos**
mientras hay una sesión midiendo, **15 segundos** en reposo. Ese segundo número
es, en el peor caso, lo que tarda el panel en enterarse de que arrancó una
sesión: el servidor no puede despertar a un cliente dormido, el aviso solo llega
en la consulta siguiente. La respuesta lleva
`ETag`, así que entre medición y medición el servidor contesta `304 Not Modified`
sin cuerpo — eso es lo que hace barato consultar cada 3 segundos.

No se usa SSE ni WebSockets a propósito: FrankenPHP arranca con dos hilos de PHP
en el plan gratuito de Render, y cada conexión persistente ocupa uno mientras
dura. Un par de pestañas del dashboard dejarían al ESP32 sin hilos donde
entregar sus mediciones.

## Base de datos

Siete tablas de dominio: `usuarios`, `individuos`, `dispositivos`, `sesiones`,
`mediciones`, `notas_campo_individuo` y `notas_campo_disp`.

Todas tienen **Row Level Security activo sin políticas**. Es intencional: la
aplicación se conecta con un rol que tiene `BYPASSRLS`, así que no se ve
afectada, mientras que las claves `anon` y `authenticated` de Supabase quedan
bloqueadas. Sin esto, cualquiera con la clave pública del proyecto podría leer y
modificar toda la base desde el navegador.

Los nombres van en minúscula a propósito: PostgreSQL pliega a minúscula todo
identificador sin comillas, así que usar mayúsculas obligaría a citar cada tabla
entre comillas dobles en cada consulta.

## Despliegue

El servicio corre en Render como contenedor Docker. Ver `render.yaml` para las
variables de entorno; las credenciales se cargan a mano en el panel, nunca en el
repositorio.

```bash
docker build -t bionea .
docker run -p 8080:8080 -e PORT=8080 --env-file .env bionea
```

Las migraciones no se aplican solas en cada despliegue. Para hacerlo, poner
`RUN_MIGRATIONS=true` en el entorno del servicio.

## Pendientes conocidos

- **No hay sistema de roles.** Todo usuario autenticado tiene acceso completo al
  panel. El alta de usuarios está restringida a `/usuarios/nuevo`, ya autenticado.
- El estado de un individuo se escribe de dos formas en distintas vistas
  (`liberado` y `Liberado/Perdido`); convendría unificarlo.
