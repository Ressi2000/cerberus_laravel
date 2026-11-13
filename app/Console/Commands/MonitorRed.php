<?php

namespace App\Console\Commands;

use App\Services\MonitorRed\MonitorRedService;
use Illuminate\Console\Command;

class MonitorRed extends Command
{
    protected $signature = 'cerberus:monitor-red
                            {--purgar : Solo borra las mediciones de latencia más antiguas que la retención configurada}';

    protected $description = 'Monitor de red: revisa por ping/TCP todos los hosts no pausados, registra caídas y notifica cambios.';

    public function handle(MonitorRedService $servicio): int
    {
        if ($this->option('purgar')) {
            $this->info('Mediciones purgadas: ' . $servicio->purgarMediciones());
            return self::SUCCESS;
        }

        $resumen = $servicio->revisarTodos();

        if ($resumen === null) {
            $this->warn('Ya hay una revisión en curso; se omite esta ejecución.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s · revisados: %d · caídas nuevas: %d · recuperados: %d',
            now()->format('Y-m-d H:i:s'),
            $resumen['revisados'],
            $resumen['caidas'],
            $resumen['recuperados'],
        ));

        return self::SUCCESS;
    }
}
