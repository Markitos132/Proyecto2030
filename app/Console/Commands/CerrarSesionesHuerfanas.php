<?php

namespace App\Console\Commands;

use App\Services\CierreDeSesiones;
use Illuminate\Console\Command;

class CerrarSesionesHuerfanas extends Command
{
    protected $signature = 'bionea:cerrar-sesiones';

    protected $description = 'Finaliza las sesiones que dejaron de recibir mediciones';

    public function handle(CierreDeSesiones $cierre): int
    {
        $cerradas = $cierre->cerrarHuerfanas();

        $this->info($cerradas === 0
            ? 'No había sesiones huérfanas.'
            : "Sesiones cerradas por inactividad: {$cerradas}.");

        return self::SUCCESS;
    }
}
