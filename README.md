# BioNEA Organiks

Sistema de monitoreo térmico para investigación en ecofisiología de lagartijas.
Un dispositivo ESP32 mide la temperatura corporal de cada individuo en el
laboratorio (también tiene un modo sin conexión para trabajo de campo) y la envía
a una API; el panel web permite gestionar ejemplares, dispositivos y sesiones de
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

### Crear el primer administrador

No hay seeders. El alta de usuarios es solo para administradores (ver
[Usuarios y administradores](#usuarios-y-administradores)), así que el primero se
crea a mano:

```bash
php artisan tinker
```

```php
\App\Models\Usuario::create([
    'nombre'   => 'Admin',
    'email'    => 'admin@ejemplo.com',
    'password' => 'una-clave-de-8-caracteres-o-mas',
    'es_admin' => true,
]);
```

La clave se guarda hasheada sola: el modelo la castea con `hashed`.

### Nota sobre el servidor de desarrollo

`php artisan serve` atiende **una petición por vez**. Alcanza para navegar el
panel, pero si además corrés el simulador del ESP32 se forma una cola y todo se
vuelve lentísimo. Para ese caso conviene servir el proyecto con Herd
(`herd link bionea`), que usa varios workers.

## Arquitectura

### Ciclo de una sesión

La sesión **no la inventa el dispositivo: se crea desde el panel**.

1. Un usuario elige un individuo y un dispositivo suyos, y fija la duración y el
   intervalo de medición. El panel no deja iniciar la sesión si el dispositivo
   está offline o si ya tiene otra sesión en curso.
2. El ESP32 consulta `GET /bionea/sesion?mac=...` y recibe los datos de la sesión.
3. Mide y manda cada medición con `POST /bionea/guardar`.
4. La sesión termina cuando el ESP32 avisa con un `fin_sesion` (porque se cumplió
   la duración o porque se la cerró con su botón), cuando el usuario la finaliza
   desde el panel, o cuando la cierra el servidor por inactividad (ver
   [Sesiones huérfanas](#sesiones-huérfanas)).

### Ingesta del ESP32

Cada medición llega con `POST /bionea/guardar` y un JSON:

```json
{
  "session_id": "12",
  "tipo": "medicion",
  "fecha": "31/07/2026",
  "hora": "23:04:32",
  "individuo": "LAG-001",
  "especie": "Liolaemus chacoensis",
  "temperatura": 28.4
}
```

- `session_id` es el que el servidor le entregó al dispositivo en
  `GET /bionea/sesion`. Se guarda en `sesiones.sesion_externa`.
- `tipo` puede ser `medicion` o `fin_sesion`. Una medición exige `temperatura`;
  un `fin_sesion` solo necesita `session_id`, `tipo`, `fecha` y `hora`.
- `fecha` (`DD/MM/YYYY`) y `hora` (`HH:MM:SS`) vienen del reloj del ESP32.
- `individuo` y `especie` son opcionales: el servidor ya los conoce por la sesión.
- El dispositivo puede mandar además `mac`. Si el `session_id` no coincide con
  ninguna sesión (por ejemplo, porque el equipo se reinició y lo perdió), se usa
  la MAC para reencontrar la sesión activa de ese dispositivo.
- **La ingesta no crea sesiones ni individuos.** Una medición que no se puede
  asociar con certeza a una sesión activa se rechaza con `409`: con el mismo
  equipo registrado en varias cuentas, adivinar mezclaría datos entre usuarios.
- Una medición cuya hora es más de un minuto posterior al cierre de la sesión se
  descarta (la respuesta sigue siendo `ok`).

La respuesta a una medición incluye `ok`, `tipo`, `id_sesion`, `session_id` y
`continuar`, que vale `false` si la sesión ya está cerrada: es la señal para que
el dispositivo deje de medir.

El endpoint exige la cabecera `X-API-Key` con el valor de `BIONEA_API_KEY`
(también acepta `Authorization: Bearer`). Dos escapes deliberados:

- **Si `BIONEA_API_KEY` está vacía, el endpoint acepta todo.** Es el
  comportamiento histórico, y permite desplegar el cambio sin cortar la ingesta
  mientras el firmware todavía no manda la clave. El orden correcto es:
  desplegar, grabar la clave en el ESP32, y recién entonces definir la variable.
- **Un usuario con sesión iniciada en el panel también pasa sin clave.** Quedó de
  cuando el simulador vivía dentro del panel (ver más abajo); hoy ninguna pantalla
  lo necesita.

`GET /bionea/sesion?mac=...` es lo que el dispositivo consulta mientras espera una
sesión, cada 30 segundos. Devuelve la sesión activa que le fue asignada desde el
panel (`session_id`, `individuo`, `especie`, y `duracion` e `intervalo`, ambos en
minutos), `204` sin cuerpo si no tiene ninguna, o `404` si su MAC no está dada de
alta. Cada consulta cuenta además como señal de vida, así que un equipo encendido
figura online aunque no esté midiendo.

`GET /bionea/health` responde el estado de la app y de la base, e incluye
`ingesta_protegida` para poder verificar de un vistazo si la clave quedó puesta.

### Estado de un dispositivo

`Dispositivo::estado_calculado` devuelve `online`, `warning` u `offline`:

- **offline**: nunca dio señal, o hace más de 15 minutos que no la da.
- **warning**: hay una sesión en curso y la última medición tardó más de dos
  intervalos.
- **online**: todo lo demás.

La señal de vida (`ultima_conexion`) se actualiza con cada consulta a
`/bionea/sesion` y con cada medición, como mucho una vez por minuto por
dispositivo. Con el firmware actual, durante una sesión el equipo no consulta: solo
habla al medir, así que con intervalos de más de 15 minutos puede figurar offline
entre una medición y la siguiente.

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
siempre. `App\Services\CierreDeSesiones` la cierra cuando:

- se cumplió su duración, más 2 minutos de margen, o
- dejó de recibir mediciones durante tres veces su intervalo configurado (mínimo
  15 minutos).

Lo dispara el comando `bionea:cerrar-sesiones`, programado cada 5 minutos en
`routes/console.php`. Eso requiere un proceso que ejecute `schedule:run` cada
minuto, y el plan gratuito de Render no ofrece cron jobs; por eso además se revisa
de forma oportunista al cargar el panel, como mucho cada 2 minutos.

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

## Usuarios y administradores

- El ingreso es con correo y contraseña. No hay autoregistro: las cuentas las crea
  un **administrador** desde `/usuarios/nuevo`, y el enlace "Nuevo usuario" del
  menú lateral solo se muestra a los administradores.
- Ser administrador depende de la columna booleana `usuarios.es_admin`, que revisa
  el middleware `es.admin` (`App\Http\Middleware\VerificarEsAdmin`). Un usuario
  común que escriba la URL a mano vuelve al panel con un aviso.
- La columna `rol` es otra cosa: es descriptiva (investigador principal, becario,
  etc.) y no da ni quita permisos.
- Cada investigador ve solo lo suyo: individuos, dispositivos y sesiones se
  filtran por `id_usuario`.

Para convertir a un usuario existente en administrador, desde el editor SQL de
Supabase:

```sql
UPDATE usuarios SET es_admin = true WHERE email = 'correo@ejemplo.com';
```

## Base de datos

Siete tablas de dominio: `usuarios`, `individuos`, `dispositivos`, `sesiones`,
`mediciones`, `notas_campo_individuo` y `notas_campo_disp`.

Todas tienen **Row Level Security activo sin políticas**. Es intencional: la
aplicación se conecta con un rol que tiene `BYPASSRLS`, así que no se ve
afectada, mientras que las claves `anon` y `authenticated` de Supabase quedan
bloqueadas. Sin esto, cualquiera con la clave pública del proyecto podría leer y
modificar toda la base desde el navegador.

Algunas relaciones no tienen borrado en cascada, a propósito:

- `mediciones.id_sesion` apunta a `sesiones`: para borrar una sesión hay que borrar
  antes sus mediciones. Lo hace `HistorialController::eliminar`, dentro de una
  transacción y solo con sesiones finalizadas del propio usuario.
- Al eliminar un dispositivo se conservan sus sesiones y mediciones: el nombre
  queda copiado en `sesiones.dispositivo_nombre` y `id_dispositivo` pasa a `null`.
  No se puede eliminar un dispositivo que tenga una sesión en curso.

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

Las migraciones no se aplican solas en cada despliegue. Para aplicarlas, poner
`RUN_MIGRATIONS=true` en el entorno del servicio, esperar a que termine el
despliegue y volver a ponerla en `false`: si queda en `true`, cada despliegue
migra la base.
