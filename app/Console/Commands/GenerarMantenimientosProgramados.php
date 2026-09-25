<?php

namespace App\Console\Commands;

use App\Models\Mantenimiento;
use App\Models\PlanMantenimiento;
use Illuminate\Console\Command;

/**
 * Revisa los planes de mantenimiento preventivo activos y, cuando se acerca
 * su fecha_proximo (dentro de PlanMantenimiento::DIAS_ANTELACION_GENERACION),
 * crea el caso "Programado" en Mantenimientos con la checklist de la
 * plantilla — el analista no tiene que acordarse de crear cada uno.
 *
 * El equipo NO se bloquea acá: un Preventivo recién bloquea al pasar a
 * "En proceso" (ver Mantenimiento::avanzarEstado()).
 */
class GenerarMantenimientosProgramados extends Command
{
    protected $signature   = 'cerberus:generar-mantenimientos-programados';
    protected $description = 'Genera los casos de mantenimiento preventivo programados según el cronograma (planes de mantenimiento).';

    public function handle(): int
    {
        PlanMantenimiento::pendientesDeGenerar()
            ->whereDoesntHave('casoAbierto')
            ->with('equipo')
            ->get()
            ->each(function (PlanMantenimiento $plan) {
                $equipo = $plan->equipo;
                if (! $equipo) return;

                $checklist = collect($plan->checklist_plantilla ?: [])
                    ->map(fn ($tarea) => ['tarea' => $tarea, 'hecho' => false])
                    ->values()
                    ->toArray();

                Mantenimiento::create([
                    'empresa_id'               => $plan->empresa_id,
                    'equipo_id'                => $equipo->id,
                    'plan_mantenimiento_id'    => $plan->id,
                    'tipo'                     => Mantenimiento::TIPO_PREVENTIVO,
                    'estado'                   => 'Programado',
                    'reportado_por_id'         => $plan->creado_por,
                    'fecha_inicio'             => $plan->fecha_proximo,
                    'proxima_fecha_programada' => $plan->fecha_proximo,
                    'frecuencia_meses'         => $plan->frecuencia_meses,
                    'descripcion'              => 'Generado automáticamente por el cronograma de mantenimiento.',
                    'checklist'                => $checklist,
                    'en_garantia'              => $equipo->fecha_garantia_fin && $equipo->fecha_garantia_fin->isFuture(),
                ]);

                $this->line("  ✓ Caso generado: {$equipo->codigo_interno} (plan #{$plan->id})");
            });

        $this->info('Generación de mantenimientos programados completada.');
        return self::SUCCESS;
    }
}
