<?php

namespace App\Console\Commands;

use App\Services\VisorService;
use Illuminate\Console\Command;

/**
 * Comando "RefrescarHechos".
 *
 * Invoca el SP sp_refrescar_hechos_asistencia para materializar la tabla de
 * hechos de asistencia desde la vista VW_Listas_asistencia_unic. La carga es
 * INCREMENTAL: solo inserta los días nuevos (data viva del checador que llega
 * con retraso), sin borrar el historial ya procesado.
 * Puede ejecutarse manualmente o vía el programador (cron).
 */
class RefrescarHechos extends Command
{
    /**
     * Firma y descripción del comando.
     *
     * @var string
     */
    protected $signature = 'erp:refrescar-hechos';

    protected $description = 'Materializa la tabla de hechos de asistencia desde la vista fuente.';

    /**
     * Ejecuta el comando.
     *
     * @return int
     */
    public function handle(): int
    {
        $filas = VisorService::refrescarHechos();
        $this->info("Tabla de hechos actualizada: {$filas} días nuevos insertados.");

        return self::SUCCESS;
    }
}