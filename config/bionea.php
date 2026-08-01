<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Clave de la API de ingesta
    |--------------------------------------------------------------------------
    |
    | Clave compartida con el firmware del ESP32. El dispositivo la envía en
    | la cabecera X-API-Key de cada POST a /bionea/guardar.
    |
    | Si queda vacía, el endpoint acepta cualquier petición, que es como
    | funcionó hasta ahora. Eso permite desplegar este cambio sin cortar la
    | ingesta: primero se despliega, después se graba la clave en el
    | firmware, y recién entonces se define la variable acá.
    |
    | Generar una con: php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"
    |
    */

    'clave_ingesta' => env('BIONEA_API_KEY'),

];
