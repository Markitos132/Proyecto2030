<#
    BioNEA Organiks — simulador del ESP32

    Reproduce exactamente lo que hace el firmware: pregunta al servidor si
    tiene una sesión asignada, la ejecuta enviando mediciones, y avisa
    cuando termina.

    A diferencia del simulador de /simulador, este corre fuera del navegador
    y sin sesión iniciada, así que atraviesa la misma autenticación por
    X-API-Key que atravesará el dispositivo real. Es la única forma de
    verificar ese camino sin el hardware en la mano.

    Uso:
        .\simular-esp32.ps1 -Clave "tu-api-key"
        .\simular-esp32.ps1 -Clave "tu-api-key" -Acelerar

    Con -Acelerar, los minutos del intervalo y de la duración se
    interpretan como segundos: una sesión de 30 min con lecturas cada
    5 min tarda 30 segundos en vez de media hora.
#>

param(
    [Parameter(Mandatory = $true)]
    [string] $Clave,

    [string] $Mac = "A0:B1:C2:D3:E4:F5",

    [string] $Url = "https://proyecto2030.onrender.com",

    [switch] $Acelerar,

    # Cada cuánto preguntar si hay sesión asignada, en segundos.
    [int] $EsperaConsulta = 10
)

$ErrorActionPreference = 'Stop'
$cabeceras = @{ 'X-API-Key' = $Clave }

function Escribir($texto, $color = 'Gray') {
    Write-Host ("[{0}] {1}" -f (Get-Date -Format 'HH:mm:ss'), $texto) -ForegroundColor $color
}

# ── Preguntar si hay una sesión asignada ───────────────────
function Buscar-Sesion {
    try {
        $r = Invoke-WebRequest -Uri "$Url/bionea/sesion?mac=$Mac" `
                               -Headers $cabeceras -Method Get -UseBasicParsing
    }
    catch {
        $codigo = $_.Exception.Response.StatusCode.value__

        switch ($codigo) {
            401 { Escribir "401 — la clave no coincide con la del servidor" Red }
            404 { Escribir "404 — el dispositivo con MAC $Mac no está dado de alta en el panel" Yellow }
            default { Escribir "Error $codigo — $($_.Exception.Message)" Red }
        }
        return $null
    }

    # 204: no hay nada asignado. Es la respuesta normal mientras nadie
    # haya creado una sesión para este equipo.
    if ($r.StatusCode -eq 204) { return $null }

    return $r.Content | ConvertFrom-Json
}

# ── Enviar una medición o el fin de sesión ─────────────────
function Enviar-Dato($cuerpo) {
    try {
        Invoke-RestMethod -Uri "$Url/bionea/guardar" -Method Post `
                          -Headers $cabeceras -ContentType 'application/json' `
                          -Body ($cuerpo | ConvertTo-Json -Compress) | Out-Null
        return $true
    }
    catch {
        $codigo = $_.Exception.Response.StatusCode.value__
        Escribir "Fallo al enviar (HTTP $codigo)" Red
        return $false
    }
}

# ── Ejecutar la sesión recibida ────────────────────────────
function Ejecutar-Sesion($s) {
    Escribir "──────────────────────────────" Cyan
    Escribir "Sesión $($s.session_id) — $($s.individuo) ($($s.especie))" Cyan
    Escribir "Duración $($s.duracion) min · intervalo $($s.intervalo) min" Cyan

    $hayRango = ($null -ne $s.temp_min) -and ($null -ne $s.temp_max)
    if ($hayRango) {
        Escribir "Rango $($s.temp_min) - $($s.temp_max) °C" Cyan
    } else {
        Escribir "Sin rango definido: todas las mediciones saldrán OK" Yellow
    }
    Escribir "──────────────────────────────" Cyan

    # Con -Acelerar los minutos se tratan como segundos.
    $unidad    = if ($Acelerar) { 1 } else { 60 }
    $intervalo = [int][Math]::Max([int]$s.intervalo, 1) * $unidad
    $total     = [int][Math]::Max([int]$s.duracion, 1) * $unidad
    $lecturas  = [int][Math]::Max([int][Math]::Floor($total / $intervalo), 1)

    $centro = if ($hayRango) { ($s.temp_min + $s.temp_max) / 2 } else { 30 }
    $ok = 0; $fuera = 0; $errores = 0

    for ($i = 1; $i -le $lecturas; $i++) {

        # Curva suave con algo de ruido, para que se parezca a una
        # temperatura real y no a una serie de números al azar.
        $fase = [Math]::Sin($i / 4.0) * 6
        $temp = [Math]::Round($centro + $fase + (Get-Random -Minimum -15 -Maximum 15) / 10.0, 2)

        $alerta = if ($hayRango -and ($temp -lt $s.temp_min -or $temp -gt $s.temp_max)) {
            "FUERA DE RANGO"
        } else { "OK" }

        $ahora = Get-Date
        $cuerpo = @{
            session_id  = $s.session_id
            tipo        = 'medicion'
            fecha       = $ahora.ToString('dd/MM/yyyy')
            hora        = $ahora.ToString('HH:mm:ss')
            individuo   = $s.individuo
            especie     = $s.especie
            temperatura = $temp
            alerta      = $alerta
        }

        if ($hayRango) {
            $cuerpo.temp_min = $s.temp_min
            $cuerpo.temp_max = $s.temp_max
        }

        if (Enviar-Dato $cuerpo) {
            $color = if ($alerta -eq 'OK') { 'Green' } else { 'Yellow' }
            Escribir ("#{0}/{1}  {2} °C  {3}" -f $i, $lecturas, $temp, $alerta) $color
            if ($alerta -eq 'OK') { $ok++ } else { $fuera++ }
        } else {
            $errores++
        }

        if ($i -lt $lecturas) { Start-Sleep -Seconds $intervalo }
    }

    $fin = Get-Date
    Enviar-Dato @{
        session_id = $s.session_id
        tipo       = 'fin_sesion'
        fecha      = $fin.ToString('dd/MM/yyyy')
        hora       = $fin.ToString('HH:mm:ss')
        individuo  = $s.individuo
        especie    = $s.especie
    } | Out-Null

    Escribir "Sesión finalizada — $ok OK, $fuera fuera de rango, $errores errores" Cyan
}

# ── Bucle principal ────────────────────────────────────────
Write-Host ""
Write-Host "  BioNEA Organiks — simulador del ESP32" -ForegroundColor White
Write-Host "  MAC: $Mac"
Write-Host "  Servidor: $Url"
if ($Acelerar) { Write-Host "  Modo acelerado: los minutos valen como segundos" -ForegroundColor Yellow }
Write-Host "  Ctrl+C para salir"
Write-Host ""

Escribir "Esperando que le asignen una sesión desde el panel..."

while ($true) {
    $sesion = Buscar-Sesion

    if ($sesion) {
        Ejecutar-Sesion $sesion
        Escribir "Esperando una sesión nueva..."
    }

    Start-Sleep -Seconds $EsperaConsulta
}
