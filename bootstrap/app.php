<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render termina el TLS en su proxy y le pasa la petición al
        // contenedor por HTTP plano, indicando el esquema original en la
        // cabecera X-Forwarded-Proto. Sin confiar en el proxy, Laravel cree
        // que la conexión es HTTP y asset() genera URLs http://, que el
        // navegador bloquea por contenido mixto: el CSS y el JS no cargan
        // (las imágenes sí, porque son contenido pasivo).
        //
        // Confiar en todos los proxies es correcto acá: en Render el único
        // camino hasta el contenedor pasa por su balanceador.
        $middleware->trustProxies(at: '*');

        // El ESP32 no tiene sesión ni puede obtener un token CSRF:
        // manda un POST plano desde el firmware.
        $middleware->validateCsrfTokens(except: [
            'bionea/guardar',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->is('bionea/*')
                || $request->expectsJson(),
        );
    })->create();
