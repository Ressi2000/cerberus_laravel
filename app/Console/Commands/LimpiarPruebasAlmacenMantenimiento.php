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
    /** Caso => [código del equipo, tipo], tal como estaban en el respaldo. */
    private const CASOS = [
        41 => ['EQ-00008', 'Preventivo'],
        42 => ['EQ-00009', 'Preventivo'],
        43 => ['EQ-00025', 'Preventivo'],
        44 => ['EQ-00055', 'Correctivo'],
        45 => ['EQ-00057', 'Correctivo'],
        46 => ['EQ-00001', 'Correctivo'],
    ];

    /** Componentes creados solo para pruebas, con todo lo que se cargó en ellos. */
    private const COMPONENTES_PRUEBA = [
        9  => ['nombre' => '16GB DDR4', 'piezas' => [4], 'movimientos' => [14, 15]],
        10 => ['nombre' => 'RAM (GB) (Cantidad: 4 / Tipo: DDR3)', 'piezas' => [5, 6, 7, 8, 9], 'movimientos' => [16, 17]],
    ];

    /** Componentes sin stock y sin ningún movimiento (id => nombre). */
    private const COMPONENTES_VACIOS = [3 => 'RAM', 4 => 'RAM DDR3 2GB para Laptop', 8 => 'bisagra'];

    /** Planes de prueba (id => [empresa_id, categoria_id]); se conserva solo el #17. */
    private const PLANES_PRUEBA = [
        1 => [6, 2], 2 => [2, 2], 3 => [6, 1], 4 => [2, 1], 5 => [6, 4], 6 => [6, 6],
        7 => [6, 8], 8 => [2, 12], 9 => [6, 2], 10 => [6, 1], 11 => [6, 2], 16 => [6, 1],
    ];

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

        $conexion = DB::connection();
        $this->line('Base de datos: <info>' . $conexion->getDatabaseName() . '</info> en <info>'
            . ($conexion->getConfig('host') ?? '—') . '</info>. Debe ser la misma de la que sacaste el respaldo.');

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
            // La foto puede estar en el disco privado o, si no se movió, en el público.
            foreach ([MantenimientoEvidencia::DISCO, MantenimientoEvidencia::DISCO_ANTERIOR] as $disco) {
                Storage::disk($disco)->delete($ruta);
            }
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

        $ids   = array_keys(self::CASOS);
        $casos = Mantenimiento::withTrashed()->with('equipo')->whereIn('id', $ids)->get()->keyBy('id');

        // Cada caso que exista tiene que ser el mismo del respaldo: si el ID
        // apunta a otro equipo o tipo, esta no es la base revisada.
        foreach ($casos as $id => $caso) {
            [$codigo, $tipo] = self::CASOS[$id];

            if ($caso->tipo !== $tipo || $caso->equipo?->codigo_interno !== $codigo) {
                throw new \RuntimeException("el caso #{$id} no es el del respaldo (se esperaba {$tipo} de {$codigo}, "
                    . "y es {$caso->tipo} de " . ($caso->equipo?->codigo_interno ?? 'otro equipo') . '). ¿Es la base correcta?');
            }
        }

        $faltan = array_diff($ids, $casos->keys()->all());
        if ($faltan) {
            $this->line('   - Ya no existen (se omiten): #' . implode(', #', $faltan) . '.');
        }

        if ($casos->isEmpty()) {
            return collect();
        }

        foreach ($casos as $caso) {
            // Libera el equipo si el caso seguía bloqueándolo.
            if ($caso->estaAbierto() && $caso->estaBloqueado() && $caso->equipo) {
                $caso->equipo->update(['estado_id' => $caso->estado_equipo_anterior_id]);
                $this->line("   - Equipo {$caso->equipo->codigo_interno} liberado (lo bloqueaba el caso #{$caso->id}).");
            }
        }

        $fotos = MantenimientoEvidencia::whereIn('mantenimiento_id', $casos->keys())->pluck('ruta_archivo');

        // Las evidencias y los componentes pedidos se borran en cascada.
        Mantenimiento::withTrashed()->whereIn('id', $casos->keys())->forceDelete();

        $this->line("   - {$casos->count()} caso(s) borrado(s): #" . $casos->keys()->implode(', #') . '.');
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
                $this->line("   - #{$id} ya no existe, se omite.");
                continue;
            }

            if ($componente->nombre !== $esperado['nombre']) {
                throw new \RuntimeException("el componente #{$id} se llama «{$componente->nombre}», no «{$esperado['nombre']}». ¿Es la base correcta?");
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

        foreach (self::COMPONENTES_VACIOS as $id => $nombre) {
            $componente = ComponenteAlmacen::withTrashed()->find($id);

            if (! $componente) {
                $this->line("   - #{$id} ya no existe, se omite.");
                continue;
            }

            if ($componente->nombre !== $nombre) {
                throw new \RuntimeException("el componente #{$id} se llama «{$componente->nombre}», no «{$nombre}». ¿Es la base correcta?");
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

        $ids    = array_keys(self::PLANES_PRUEBA);
        $planes = PlanMantenimiento::withTrashed()->whereIn('id', $ids)->get();

        foreach ($planes as $plan) {
            [$empresa, $categoria] = self::PLANES_PRUEBA[$plan->id];

            if ((int) $plan->empresa_id !== $empresa || (int) $plan->categoria_id !== $categoria) {
                throw new \RuntimeException("el plan #{$plan->id} no es el del respaldo (otra empresa o categoría). ¿Es la base correcta?");
            }
        }

        if ($planes->isEmpty()) {
            $this->line('   - Ya no existen, se omite.');
            return;
        }

        $conCasos = Mantenimiento::withTrashed()->whereIn('plan_mantenimiento_id', $ids)->pluck('id');
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

        if ($pieza && ((int) $pieza->equipo_origen_id !== 17 || (int) $pieza->componente_almacen_id !== 6)) {
            throw new \RuntimeException('la pieza #' . self::PIEZA_DISCO . ' no es el disco rescatado de EQ-00017. ¿Es la base correcta?');
        }

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

        if ($componente && $componente->nombre !== 'Disco Duro') {
            throw new \RuntimeException("el componente #" . self::COMPONENTE_DISCO_DURO . " se llama «{$componente->nombre}», no «Disco Duro». ¿Es la base correcta?");
        }

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
