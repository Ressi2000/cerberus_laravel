<?php

namespace App\Console\Commands;

use App\Models\Mantenimiento;
use App\Models\MantenimientoEvidencia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Utilidad de un solo uso para vaciar por completo la tabla `mantenimientos`
 * (Preventivo y Correctivo) durante las pruebas del cronograma — NO es una
 * migración: una migración queda para siempre en el historial de despliegue
 * y corre automáticamente en cada `php artisan migrate`, lo cual es
 * peligroso para un borrado masivo de datos. Esto se corre a mano, una vez,
 * cuando se decide reiniciar el módulo de Mantenimientos desde cero.
 *
 * Antes de borrar:
 *   1. Libera cualquier equipo que siga bloqueado por un caso abierto (si
 *      no, el equipo queda "pegado" en estado bloqueado para siempre, sin
 *      ningún caso que lo pueda liberar).
 *   2. Borra del disco las fotos de evidencia (la cascada de MySQL borra
 *      las filas de mantenimiento_evidencias, pero no los archivos).
 *
 * Lo que SÍ se conserva (por diseño, ver foreignId(...)->nullOnDelete() en
 * las migraciones correspondientes): piezas_extraidas,
 * piezas_extraidas_movimientos y movimientos_componentes NO se borran —
 * solo pierden la referencia al mantenimiento_id de origen. Es historial de
 * piezas/almacén, un dominio aparte del de los casos de mantenimiento.
 * stock_actual de componentes_almacen tampoco se revierte.
 */
class LimpiarMantenimientosPrueba extends Command
{
    protected $signature   = 'cerberus:limpiar-mantenimientos-prueba {--force : Omite la confirmación interactiva}';
    protected $description = 'BORRA TODOS los casos de mantenimientos/reparaciones (Preventivo y Correctivo) — uso único para resetear datos de prueba.';

    public function handle(): int
    {
        $total = Mantenimiento::withTrashed()->count();

        if ($total === 0) {
            $this->info('La tabla mantenimientos ya está vacía — nada que borrar.');
            return self::SUCCESS;
        }

        $this->warn("Esto va a borrar PERMANENTEMENTE los {$total} caso(s) de mantenimientos/reparaciones de la base de datos (todas las empresas, Preventivo y Correctivo).");
        $this->line('No se puede deshacer. El historial de piezas/almacén (piezas_extraidas, movimientos) se conserva, solo pierde la referencia al caso.');

        if (! $this->option('force') && ! $this->confirm('¿Confirmas que quieres borrar TODOS los mantenimientos?')) {
            $this->info('Cancelado — no se borró nada.');
            return self::SUCCESS;
        }

        DB::transaction(function () {
            // 1) Liberar equipos que sigan bloqueados por un caso todavía
            //    abierto — si no, se quedan "En mantenimiento"/"En reparación"
            //    para siempre sin ningún caso que los pueda liberar.
            $bloqueados = Mantenimiento::whereNotIn('estado', Mantenimiento::ESTADOS_TERMINALES)
                ->whereNotNull('estado_equipo_anterior_id')
                ->with('equipo')
                ->get();

            foreach ($bloqueados as $mantenimiento) {
                if ($mantenimiento->equipo) {
                    $mantenimiento->equipo->update(['estado_id' => $mantenimiento->estado_equipo_anterior_id]);
                    $this->line("  → Equipo {$mantenimiento->equipo->codigo_interno} liberado (estaba bloqueado por el caso #{$mantenimiento->id}).");
                }
            }

            // 2) Borrar del disco las fotos de evidencia antes de que la
            //    cascada de MySQL borre las filas (la cascada no dispara
            //    eventos de Eloquent, así que esto hay que hacerlo a mano).
            $rutas = MantenimientoEvidencia::pluck('ruta_archivo');
            foreach ($rutas as $ruta) {
                Storage::disk('public')->delete($ruta);
            }
            $this->line("  → {$rutas->count()} foto(s) de evidencia borrada(s) del disco.");

            // 3) Borrar todo. Equipo::whereDoesntHave() en el comando de
            //    generación ya no encontrará ningún caso abierto.
            Mantenimiento::withTrashed()->forceDelete();
        });

        $this->info("Listo: {$total} caso(s) de mantenimientos/reparaciones borrado(s).");
        return self::SUCCESS;
    }
}
