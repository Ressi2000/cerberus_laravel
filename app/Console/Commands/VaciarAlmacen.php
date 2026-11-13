<?php

namespace App\Console\Commands;

use App\Models\ComponenteAlmacen;
use App\Models\MantenimientoComponente;
use App\Models\MovimientoComponente;
use App\Models\PiezaExtraida;
use App\Models\PiezaExtraidaMovimiento;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Vacía el almacén para empezar de cero con las familias de piezas.
 *
 * - Borra TODOS los componentes del almacén (de todas las empresas) y su
 *   historial de entradas y salidas.
 * - Las piezas que estaban en el almacén quedan "retiradas", con un
 *   movimiento que lo explica.
 * - Las piezas instaladas en equipos, en depósitos o ya retiradas se
 *   conservan: son parte del historial de los equipos. Solo dejan de estar
 *   ligadas a un componente.
 *
 * Si algún caso de mantenimiento pidió un componente, se detiene: borrarlo
 * dejaría ese caso sin el registro de lo que se le entregó.
 *
 * Sin --ejecutar solo SIMULA (hace todo en una transacción y la revierte).
 */
class VaciarAlmacen extends Command
{
    protected $signature = 'cerberus:vaciar-almacen
                            {--usuario= : ID del Administrador que hace la limpieza (queda en los movimientos de las piezas)}
                            {--ejecutar : Aplica los cambios. Sin esta opción solo simula}';

    protected $description = 'Vacía el almacén (componentes y su historial de stock) para empezar de cero. Simula por defecto.';

    public function handle(): int
    {
        $usuario = User::find($this->option('usuario'));

        if (! $usuario || ! $usuario->hasRole('Administrador')) {
            $this->error('Indica con --usuario=ID el Administrador que hace la limpieza.');

            return self::FAILURE;
        }

        $ejecutar = (bool) $this->option('ejecutar');
        $conexion = DB::connection();

        $this->line('Base de datos: <info>' . $conexion->getDatabaseName() . '</info> en <info>' . ($conexion->getConfig('host') ?? '—') . '</info>.');
        $this->warn($ejecutar ? 'EJECUTANDO: los cambios son permanentes.' : 'SIMULACIÓN: no se va a guardar nada. Para aplicar, agrega --ejecutar.');
        $this->newLine();

        $componentes = ComponenteAlmacen::withTrashed()->with('empresa')->orderBy('id')->get();

        if ($componentes->isEmpty()) {
            $this->info('El almacén ya está vacío.');

            return self::SUCCESS;
        }

        $pedidos = MantenimientoComponente::whereIn('componente_id', $componentes->pluck('id'))->pluck('mantenimiento_id')->unique();
        if ($pedidos->isNotEmpty()) {
            $this->error('Se detuvo sin cambiar nada: estos casos pidieron componentes del almacén: #' . $pedidos->implode(', #')
                . '. Ciérralos o elimínalos antes de vaciar el almacén.');

            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            $this->table(
                ['ID', 'Componente', 'Empresa', 'Stock'],
                $componentes->map(fn ($c) => [$c->id, $c->nombre, $c->empresa->nombre ?? '—', $c->stock_actual])->all(),
            );

            // Piezas que estaban en el almacén → retiradas.
            $enAlmacen = PiezaExtraida::whereIn('componente_almacen_id', $componentes->pluck('id'))
                ->where('estado', PiezaExtraida::ESTADO_EN_ALMACEN)
                ->get();

            foreach ($enAlmacen as $pieza) {
                $pieza->update(['estado' => PiezaExtraida::ESTADO_RETIRADA]);
                $pieza->movimientos()->create([
                    'tipo'           => PiezaExtraidaMovimiento::TIPO_SALIDA_AJUSTE,
                    'registrado_por' => $usuario->id,
                    'observaciones'  => 'Se vació el almacén para empezar de cero con las familias de piezas.',
                ]);
            }

            $conservadas = PiezaExtraida::whereIn('componente_almacen_id', $componentes->pluck('id'))->count();
            $movimientos = MovimientoComponente::whereIn('componente_id', $componentes->pluck('id'))->delete();

            // El resto de las piezas (instaladas, en depósito, retiradas) se
            // conserva; la llave queda en NULL al borrar el componente.
            ComponenteAlmacen::withTrashed()->whereIn('id', $componentes->pluck('id'))->forceDelete();

            $this->line("   - {$componentes->count()} componente(s) borrado(s) con {$movimientos} movimiento(s) de stock.");
            $this->line("   - {$enAlmacen->count()} pieza(s) que estaban en el almacén quedaron retiradas.");
            $this->line("   - {$conservadas} pieza(s) en total conservan su historial (instaladas en equipos, en depósitos o retiradas).");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Se detuvo sin cambiar nada: ' . $e->getMessage());

            return self::FAILURE;
        }

        if (! $ejecutar) {
            DB::rollBack();
            $this->newLine();
            $this->info('Simulación terminada: no se guardó nada. Si todo se ve bien, repite con --ejecutar.');

            return self::SUCCESS;
        }

        DB::commit();
        $this->newLine();
        $this->info('Almacén vaciado.');

        return self::SUCCESS;
    }
}
