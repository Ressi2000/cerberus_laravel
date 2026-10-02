<?php

namespace App\Console\Commands;

use App\Models\Mantenimiento;
use App\Models\PlanMantenimiento;
use Illuminate\Console\Command;

/**
 * Revisa los planes de mantenimiento preventivo activos (por categoría +
 * empresa) y, cuando se acerca su fecha_proximo (dentro de
 * PlanMantenimiento::DIAS_ANTELACION_GENERACION), genera el LOTE completo:
 * un caso "Programado" por cada equipo activo de esa categoría/empresa que
 * todavía no tenga un mantenimiento abierto — el analista no tiene que
 * armar el lote a mano ni acordarse de crear cada caso.
 *
 * El equipo NO se bloquea acá: un Preventivo recién bloquea al pasar a
 * "En proceso" (ver Mantenimiento::avanzarEstado()). El plan solo avanza a
 * su siguiente fecha_proximo cuando TODOS los casos del lote se cierran
 * (ver PlanMantenimiento::avanzarSiLoteCompleto()).
 */
class GenerarMantenimientosProgramados extends Command
{
    protected $signature   = 'cerberus:generar-mantenimientos-programados';
    protected $description = 'Genera los lotes de mantenimiento preventivo programados según el cronograma (planes por categoría/empresa).';

    public function handle(): int
    {
        $planes = PlanMantenimiento::pendientesDeGenerar()->with(['categoria', 'empresa'])->get();

        if ($planes->isEmpty()) {
            $this->info('No hay planes activos con fecha_proximo dentro del margen de antelación (' . PlanMantenimiento::DIAS_ANTELACION_GENERACION . ' días) — nada que generar.');
            return self::SUCCESS;
        }

        $planes->each(function (PlanMantenimiento $plan) {
            $nombre = "Plan #{$plan->id} ({$plan->categoria?->nombre} — {$plan->empresa?->nombre}, {$plan->fecha_proximo->format('d/m/Y')})";

            // Ya se generó el lote de esta fecha_proximo — no duplicar.
            if ($plan->loteGenerado()) {
                $this->line("  → {$nombre}: el lote de esta fecha ya estaba generado, se omite.");
                return;
            }

            $equipos = $plan->equiposAlcanzados()
                ->whereDoesntHave('mantenimientos', fn ($q) => $q->whereNotIn('estado', Mantenimiento::ESTADOS_TERMINALES))
                ->get();

            if ($equipos->isEmpty()) {
                $this->warn("  → {$nombre}: no se generó nada — no hay equipos disponibles en este alcance (o todos ya tienen un caso abierto).");
                return;
            }

            $checklist = collect($plan->checklist_plantilla ?: [])
                ->map(fn ($tarea) => ['tarea' => $tarea, 'hecho' => false])
                ->values()
                ->toArray();

            foreach ($equipos as $equipo) {
                Mantenimiento::create([
                    'empresa_id'               => $plan->empresa_id,
                    'equipo_id'                => $equipo->id,
                    'plan_mantenimiento_id'    => $plan->id,
                    'tipo'                     => Mantenimiento::TIPO_PREVENTIVO,
                    'estado'                   => 'Programado',
                    'reportado_por_id'         => $plan->creado_por,
                    'responsable_id'           => $plan->responsable_id,
                    'fecha_inicio'             => $plan->fecha_proximo,
                    'fecha_fin_estimada'       => $plan->fechaFinEstimada(),
                    'proxima_fecha_programada' => $plan->fecha_proximo,
                    'frecuencia_meses'         => $plan->frecuencia_meses,
                    'descripcion'              => 'Generado automáticamente por el cronograma de mantenimiento.',
                    'checklist'                => $checklist,
                    'en_garantia'              => $equipo->fecha_garantia_fin && $equipo->fecha_garantia_fin->isFuture(),
                ]);

                $this->line("  ✓ Caso generado: {$equipo->codigo_interno} (plan #{$plan->id})");
            }
        });

        $this->info('Generación de lotes de mantenimiento programados completada.');
        return self::SUCCESS;
    }
}
