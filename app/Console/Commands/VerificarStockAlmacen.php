<?php

namespace App\Console\Commands;

use App\Models\ComponenteAlmacen;
use App\Models\PiezaExtraida;
use App\Models\PiezaExtraidaMovimiento;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Revisa que cada componente del almacén cumpla la regla:
 *
 *     stock_actual >= piezas con identidad en "en_almacen"
 *
 * Antes de que toda salida de stock exigiera indicar qué piezas salen, una
 * salida por "Ajuste manual" o "Dañado" (sin pieza elegida) bajaba el stock
 * pero dejaba las piezas en "en_almacen". Este comando lista esos
 * componentes para corregirlos a mano. Por sí solo no cambia nada, porque
 * no hay forma de saber cuál de las piezas es la que ya no está.
 *
 * Una vez revisado físicamente, para cada pieza sobrante:
 *   - si YA NO ESTÁ:  --retirar=12,15 --usuario=ID la marca como "retirada"
 *     sin tocar el stock (el stock ya se había descontado en su momento).
 *   - si SÍ ESTÁ:     --reponer=12,15 --usuario=ID devuelve esa unidad al
 *     stock (+1 por pieza, con su entrada en el kardex); la pieza no cambia.
 * Ambas dejan rastro con el usuario indicado, solo aceptan piezas de
 * componentes descuadrados y nunca más piezas de las que sobran.
 */
class VerificarStockAlmacen extends Command
{
    protected $signature   = 'cerberus:verificar-stock-almacen
                              {--retirar= : IDs de piezas (separados por coma) que ya no están físicamente y se marcan como retiradas}
                              {--reponer= : IDs de piezas (separados por coma) que SÍ están físicamente: se devuelven al stock}
                              {--usuario= : ID del Administrador que hace la corrección (obligatorio con --retirar o --reponer)}';
    protected $description = 'Lista los componentes del almacén con más piezas "en almacén" que stock (descuadres a revisar a mano).';

    public function handle(): int
    {
        if ($this->option('retirar') && $this->option('reponer')) {
            $this->error('Usa --retirar o --reponer, no los dos a la vez.');

            return self::FAILURE;
        }

        if ($this->option('retirar') || $this->option('reponer')) {
            return $this->corregir($this->option('retirar') ? 'retirar' : 'reponer');
        }

        $descuadrados = $this->descuadrados();

        if ($descuadrados->isEmpty()) {
            $this->info('Todo cuadra: ningún componente tiene más piezas en almacén que stock.');

            return self::SUCCESS;
        }

        $this->warn("{$descuadrados->count()} componente(s) con descuadre. Revisa físicamente cada pieza: si ya no está usa --retirar=ID,ID; si sí está usa --reponer=ID,ID (ambos con --usuario=ID).");

        $this->table(
            ['ID', 'Empresa', 'Componente', 'Stock', 'Piezas en almacén', 'Sobran', 'IDs de piezas en almacén'],
            $descuadrados->map(fn ($c) => [
                $c->id,
                $c->empresa?->nombre ?? '—',
                $c->nombre . ($c->trashed() ? ' (eliminado)' : ''),
                $c->stock_actual,
                $c->piezas_en_almacen,
                $c->piezas_en_almacen - $c->stock_actual,
                $c->piezasEnAlmacen()->pluck('id')->implode(', '),
            ])->values()->all(),
        );

        return self::FAILURE;
    }

    private function descuadrados()
    {
        return ComponenteAlmacen::withTrashed()
            ->with('empresa')
            ->withCount(['piezasExtraidas as piezas_en_almacen' => fn ($q) => $q
                ->where('estado', PiezaExtraida::ESTADO_EN_ALMACEN),
            ])
            ->get()
            ->filter(fn ($c) => $c->piezas_en_almacen > $c->stock_actual)
            ->keyBy('id');
    }

    private function corregir(string $accion): int
    {
        $usuario = User::find($this->option('usuario'));

        if (! $usuario || ! $usuario->hasRole('Administrador')) {
            $this->error('Indica con --usuario=ID el Administrador que hace la corrección (queda en el historial de cada pieza).');

            return self::FAILURE;
        }

        $ids = collect(explode(',', (string) $this->option($accion)))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique();

        $piezas       = PiezaExtraida::whereIn('id', $ids)->where('estado', PiezaExtraida::ESTADO_EN_ALMACEN)->get();
        $descuadrados = $this->descuadrados();

        if ($piezas->count() !== $ids->count()) {
            $this->error('Alguno de los IDs no existe o la pieza ya no está "en almacén".');

            return self::FAILURE;
        }

        foreach ($piezas->groupBy('componente_almacen_id') as $componenteId => $grupo) {
            $componente = $descuadrados->get($componenteId);

            if (! $componente) {
                $this->error("El componente #{$componenteId} no está descuadrado: esas piezas se retiran con una salida normal desde Almacén.");

                return self::FAILURE;
            }

            $sobran = $componente->piezas_en_almacen - $componente->stock_actual;

            if ($grupo->count() > $sobran) {
                $this->error("En «{$componente->nombre}» sobran {$sobran} pieza(s) y elegiste {$grupo->count()}.");

                return self::FAILURE;
            }
        }

        DB::transaction(function () use ($piezas, $usuario, $accion) {
            if ($accion === 'reponer') {
                foreach ($piezas->groupBy('componente_almacen_id') as $componenteId => $grupo) {
                    ComponenteAlmacen::withTrashed()->findOrFail($componenteId)->registrarEntrada(
                        $grupo->count(),
                        $usuario,
                        'Corrección de descuadre',
                        'Unidades que seguían físicamente en el almacén: piezas #' . $grupo->pluck('id')->implode(', #'),
                    );
                }

                return;
            }

            foreach ($piezas as $pieza) {
                $pieza->update(['estado' => PiezaExtraida::ESTADO_RETIRADA]);

                $pieza->movimientos()->create([
                    'tipo'           => PiezaExtraidaMovimiento::TIPO_SALIDA_AJUSTE,
                    'registrado_por' => $usuario->id,
                    'observaciones'  => 'Corrección de descuadre: el stock ya se había descontado sin indicar esta pieza.',
                ]);
            }
        });

        $this->info($accion === 'reponer'
            ? "{$piezas->count()} unidad(es) devueltas al stock."
            : "{$piezas->count()} pieza(s) marcadas como retiradas.");

        return self::SUCCESS;
    }
}
