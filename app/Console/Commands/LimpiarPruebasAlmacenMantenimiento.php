<?php

namespace App\Console\Commands;

use App\Models\ComponenteAlmacen;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\MantenimientoComponente;
use App\Models\MantenimientoEvidencia;
use App\Models\MovimientoComponente;
use App\Models\PiezaExtraida;
use App\Models\PiezaExtraidaMovimiento;
use App\Models\PlanMantenimiento;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Limpieza ÚNICA de los datos de prueba de Mantenimiento/Reparación, Almacén
 * y Cronograma, acordada a partir del respaldo de la base del 09/11/2026.
 *
 * Los IDs están fijos a propósito: cada uno se revisó a mano. Antes de tocar
 * cada grupo, el comando verifica que los registros sigan como estaban en ese
 * respaldo; si algo cambió (por ejemplo, alguien usó un componente después),
 * se detiene sin cambiar nada.
 *
 * Sin --ejecutar solo SIMULA: hace todo dentro de una transacción, muestra el
 * resultado y la revierte. Las fotos de evidencia se borran del disco solo al
 * ejecutar de verdad, después de confirmar la transacción.
 */
class LimpiarPruebasAlmacenMantenimiento extends Command
{
    protected $signature = 'cerberus:limpiar-pruebas-almacen-mantenimiento
                            {--usuario= : ID del Administrador que hace la limpieza (queda en los movimientos)}
                            {--ejecutar : Aplica los cambios. Sin esta opción solo simula}';

    protected $description = 'Limpieza única de datos de prueba de Mantenimiento, Almacén y Cronograma (simula por defecto).';

    /** Casos de prueba: todos los que había en el respaldo. */
    private const CASOS = [41, 42, 43, 44, 45, 46];

    /** Componentes creados solo para pruebas, con todo lo que se cargó en ellos. */
    private const COMPONENTES_PRUEBA = [
        9  => ['piezas' => [4], 'movimientos' => [14, 15]],
        10 => ['piezas' => [5, 6, 7, 8, 9], 'movimientos' => [16, 17]],
    ];

    /** Componentes sin stock y sin ningún movimiento. */
    private const COMPONENTES_VACIOS = [3, 4, 8];

    /** Planes eliminados o inactivos de prueba (se conserva solo el #17). */
    private const PLANES_PRUEBA = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 16];

    /** Disco 200GB HDD rescatado de EQ-00017: su stock ya se había usado el 02/10. */
    private const PIEZA_DISCO = 2;

    /** «Disco Duro»: stock 5 sin respaldo (sus salidas fueron de casos ya borrados). */
    private const COMPONENTE_DISCO_DURO = 1;
    private const STOCK_DISCO_DURO      = 5;

    /** Entrada del procesador de EQ-00054 con el motivo equivocado. */
    private const MOVIMIENTO_PROCESADOR = 13;

    private bool $ejecutar = false;

    public function handle(): int
    {
        $usuario = User::find($this->option('usuario'));

        if (! $usuario || ! $usuario->hasRole('Administrador')) {
            $this->error('Indica con --usuario=ID el Administrador que hace la limpieza.');

            return self::FAILURE;
        }

        $this->ejecutar = (bool) $this->option('ejecutar');

        $this->warn($this->ejecutar
            ? 'EJECUTANDO la limpieza. Los cambios son permanentes.'
            : 'SIMULACIÓN: no se va a guardar nada. Para aplicar, agrega --ejecutar.');
        $this->newLine();

        $fotos = collect();

        DB::beginTransaction();

        try {
            $fotos = $this->borrarCasos();
            $this->borrarComponentesPrueba();
            $this->borrarComponentesVacios();
            $this->borrarPlanesPrueba();
            $this->retirarDisco($usuario);
            $this->vaciarDiscoDuro($usuario);
            $this->corregirMotivoProcesador();
        } catch (\RuntimeException $e) {
            DB::rollBack();
            $this->newLine();
            $this->error('Se detuvo sin cambiar nada: ' . $e->getMessage());

            return self::FAILURE;
        }

        if (! $this->ejecutar) {
            DB::rollBack();
            $this->newLine();
            $this->info('Simulación terminada: no se guardó nada. Si todo se ve bien, repite el comando con --ejecutar.');

            return self::SUCCESS;
        }

        DB::commit();

        foreach ($fotos as $ruta) {
            Storage::disk('public')->delete($ruta);
        }

        $this->newLine();
        $this->info("Limpieza aplicada. {$fotos->count()} foto(s) de evidencia borrada(s) del disco.");
        $this->line('Revisa el resultado con: php artisan cerberus:verificar-stock-almacen');

        return self::SUCCESS;
    }

    // ─── 1. Casos ──────────────────────────────────────────────────────────

    private function borrarCasos()
    {
        $this->line('<comment>1. Casos de mantenimiento/reparación de prueba</comment>');

        $casos = Mantenimiento::withTrashed()->with('equipo')->whereIn('id', self::CASOS)->get();

        if ($casos->count() !== count(self::CASOS)) {
            throw new \RuntimeException('faltan casos de prueba (¿ya se borraron?). Esperados: #' . implode(', #', self::CASOS) . '.');
        }

        foreach ($casos as $caso) {
            // Libera el equipo si el caso seguía bloqueándolo.
            if ($caso->estaAbierto() && $caso->estaBloqueado() && $caso->equipo) {
                $caso->equipo->update(['estado_id' => $caso->estado_equipo_anterior_id]);
                $this->line("   - Equipo {$caso->equipo->codigo_interno} liberado (lo bloqueaba el caso #{$caso->id}).");
            }
        }

        $fotos = MantenimientoEvidencia::whereIn('mantenimiento_id', self::CASOS)->pluck('ruta_archivo');

        // Las evidencias y los componentes pedidos se borran en cascada.
        Mantenimiento::withTrashed()->whereIn('id', self::CASOS)->forceDelete();

        $this->line('   - ' . count(self::CASOS) . " caso(s) borrado(s): #" . implode(', #', self::CASOS) . ".");
        $this->line("   - {$fotos->count()} foto(s) de evidencia " . ($this->ejecutar ? 'se borrarán' : 'se borrarían') . ' del disco.');

        return $fotos;
    }

    // ─── 2. Componentes de prueba ──────────────────────────────────────────

    private function borrarComponentesPrueba(): void
    {
        $this->line('<comment>2. Componentes cargados solo para pruebas</comment>');

        foreach (self::COMPONENTES_PRUEBA as $id => $esperado) {
            $componente = ComponenteAlmacen::withTrashed()->find($id);

            if (! $componente) {
                throw new \RuntimeException("el componente #{$id} ya no existe.");
            }

            $piezas      = PiezaExtraida::where('componente_almacen_id', $id)->pluck('id')->sort()->values()->all();
            $movimientos = MovimientoComponente::where('componente_id', $id)->pluck('id')->sort()->values()->all();
            $pedidos     = MantenimientoComponente::where('componente_id', $id)->count();

            if ($piezas !== $esperado['piezas'] || $movimientos !== $esperado['movimientos'] || $pedidos > 0) {
                throw new \RuntimeException("«{$componente->nombre}» (#{$id}) cambió desde el respaldo (piezas o movimientos nuevos). Revísalo antes de borrarlo.");
            }

            PiezaExtraidaMovimiento::whereIn('pieza_id', $piezas)->delete();
            PiezaExtraida::whereIn('id', $piezas)->delete();
            MovimientoComponente::whereIn('id', $movimientos)->delete();
            $componente->forceDelete();

            $this->line("   - «{$componente->nombre}» (#{$id}) borrado con " . count($piezas) . ' pieza(s) y ' . count($movimientos) . ' movimiento(s).');
        }
    }

    // ─── 3. Componentes vacíos ─────────────────────────────────────────────

    private function borrarComponentesVacios(): void
    {
        $this->line('<comment>3. Componentes vacíos que nunca se usaron</comment>');

        foreach (self::COMPONENTES_VACIOS as $id) {
            $componente = ComponenteAlmacen::withTrashed()->find($id);

            if (! $componente) {
                $this->line("   - #{$id} ya no existe, se omite.");
                continue;
            }

            $usado = $componente->stock_actual > 0
                || MovimientoComponente::where('componente_id', $id)->exists()
                || PiezaExtraida::where('componente_almacen_id', $id)->exists()
                || MantenimientoComponente::where('componente_id', $id)->exists();

            if ($usado) {
                throw new \RuntimeException("«{$componente->nombre}» (#{$id}) ya tiene stock o movimientos. Revísalo antes de borrarlo.");
            }

            $componente->forceDelete();
            $this->line("   - «{$componente->nombre}» (#{$id}) borrado.");
        }
    }

    // ─── 4. Planes de prueba ───────────────────────────────────────────────

    private function borrarPlanesPrueba(): void
    {
        $this->line('<comment>4. Planes de cronograma de prueba</comment>');

        $planes = PlanMantenimiento::withTrashed()->whereIn('id', self::PLANES_PRUEBA)->get();

        $conCasos = Mantenimiento::withTrashed()->whereIn('plan_mantenimiento_id', self::PLANES_PRUEBA)->pluck('id');
        if ($conCasos->isNotEmpty()) {
            throw new \RuntimeException('hay casos nuevos generados por planes de prueba (#' . $conCasos->implode(', #') . '). Revísalos antes.');
        }

        $activosNuevos = $planes->filter(fn ($p) => ! $p->trashed() && $p->activo);
        if ($activosNuevos->isNotEmpty()) {
            throw new \RuntimeException('el/los plan(es) #' . $activosNuevos->pluck('id')->implode(', #') . ' están activos otra vez. Revísalos antes.');
        }

        // Los equipos de cada plan (plan_mantenimiento_equipos) se borran en cascada.
        PlanMantenimiento::withTrashed()->whereIn('id', $planes->pluck('id'))->forceDelete();

        $this->line("   - {$planes->count()} plan(es) borrado(s): #" . $planes->pluck('id')->implode(', #') . '. Se conserva el #17.');
    }

    // ─── 5. Disco 200GB HDD (pieza #2) ─────────────────────────────────────

    private function retirarDisco(User $usuario): void
    {
        $this->line('<comment>5. Disco 200GB HDD 3.5 (pieza #' . self::PIEZA_DISCO . ')</comment>');

        $pieza = PiezaExtraida::find(self::PIEZA_DISCO);

        if (! $pieza || $pieza->estado !== PiezaExtraida::ESTADO_EN_ALMACEN) {
            $this->line('   - Ya no figura en almacén, se omite.');
            return;
        }

        $componente = $pieza->componenteAlmacen;
        if ($componente && $componente->stock_actual >= $componente->piezasEnAlmacen()->count()) {
            $this->line('   - Su componente ya no está descuadrado, se omite (revísalo a mano).');
            return;
        }

        $pieza->update(['estado' => PiezaExtraida::ESTADO_RETIRADA]);
        $pieza->movimientos()->create([
            'tipo'           => PiezaExtraidaMovimiento::TIPO_SALIDA_AJUSTE,
            'registrado_por' => $usuario->id,
            'observaciones'  => 'Limpieza de datos: su stock se usó el 02/10 en un caso ya borrado. Ubicación física no verificada.',
        ]);

        $this->line('   - Marcada como retirada.');
    }

    // ─── 6. Stock sin respaldo de «Disco Duro» ─────────────────────────────

    private function vaciarDiscoDuro(User $usuario): void
    {
        $this->line('<comment>6. Stock sin respaldo de «Disco Duro»</comment>');

        $componente = ComponenteAlmacen::find(self::COMPONENTE_DISCO_DURO);

        if (! $componente || $componente->stock_actual === 0) {
            $this->line('   - Ya está en 0, se omite.');
            return;
        }

        if ($componente->stock_actual !== self::STOCK_DISCO_DURO) {
            throw new \RuntimeException("«Disco Duro» tiene stock {$componente->stock_actual} y se esperaba " . self::STOCK_DISCO_DURO . '. Revísalo antes.');
        }

        $componente->registrarAjusteSalida(
            $componente->stock_actual,
            $usuario,
            'Limpieza de datos',
            'Stock sin respaldo: sus movimientos eran de casos de prueba ya borrados.',
        );

        $this->line('   - Llevado de ' . self::STOCK_DISCO_DURO . ' a 0 con una salida «Limpieza de datos». («Pantalla» ya estaba en 0.)');
    }

    // ─── 7. Motivo del procesador ──────────────────────────────────────────

    private function corregirMotivoProcesador(): void
    {
        $this->line('<comment>7. Motivo del procesador de EQ-00054</comment>');

        $cambiados = MovimientoComponente::whereKey(self::MOVIMIENTO_PROCESADOR)
            ->where('motivo', 'Rescate por sustitución en reparación')
            ->update(['motivo' => 'Rescate por desarme de equipo']);

        $this->line($cambiados ? '   - Corregido a «Rescate por desarme de equipo».' : '   - Ya estaba corregido, se omite.');
    }
}
