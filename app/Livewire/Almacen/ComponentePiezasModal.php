<?php

namespace App\Livewire\Almacen;

use App\Models\ComponenteAlmacen;
use App\Models\MovimientoComponente;
use App\Models\PiezaExtraida;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Historial de una variante del almacén (solo lectura).
 *
 * - "En stock": las piezas que hay hoy, con su serial y de dónde salió cada
 *   una (solo familias por unidad).
 * - "Todas las piezas": también las que ya se usaron, se descartaron o se
 *   retiraron, con dónde están hoy. Cada una abre su ficha con el recorrido.
 * - "Entradas y salidas": el kardex de cantidades (para familias a granel es
 *   la única traza: cuánto entró, cuánto salió y a qué caso).
 */
class ComponentePiezasModal extends Component
{
    public bool $open = false;

    public string $pestana = 'stock'; // stock | piezas | movimientos

    /** Serial para una unidad que está en stock sin serial. */
    public string $serialNuevo = '';

    // Locked: solo se fija en abrir(), después de autorizar — no se puede
    // cambiar desde el navegador para espiar piezas de otro componente.
    #[Locked]
    public ?int $componenteId = null;

    /** Cuántos renglones se muestran como máximo en cada pestaña. */
    const LIMITE = 100;

    #[On('openComponentePiezas')]
    public function abrir(int $componenteId): void
    {
        $componente = ComponenteAlmacen::findOrFail($componenteId);
        $this->authorize('view', $componente);

        $this->componenteId = $componenteId;
        $this->pestana = $componente->esAGranel() ? 'movimientos' : 'stock';
        unset($this->componente, $this->piezas, $this->todasLasPiezas, $this->usos, $this->resumen, $this->retirosGranel);
        $this->open = true;
    }

    public function verPestana(string $pestana): void
    {
        if (in_array($pestana, ['stock', 'piezas', 'movimientos'], true)) {
            $this->pestana = $pestana;
        }
    }

    #[Computed]
    public function componente(): ?ComponenteAlmacen
    {
        return $this->componenteId ? ComponenteAlmacen::with('familia')->find($this->componenteId) : null;
    }

    /** Unidades físicas que suman el stock de esta variante, con su origen. */
    #[Computed]
    public function piezas()
    {
        if (! $this->componenteId) {
            return collect();
        }

        return PiezaExtraida::where('componente_almacen_id', $this->componenteId)
            ->where('estado', PiezaExtraida::ESTADO_EN_ALMACEN)
            ->with(['equipoOrigen', 'extraidoPor'])
            ->latest()
            ->get();
    }

    /** Todas las piezas que alguna vez fueron de esta variante, estén donde estén. */
    #[Computed]
    public function todasLasPiezas()
    {
        if (! $this->componenteId) {
            return collect();
        }

        return PiezaExtraida::where('componente_almacen_id', $this->componenteId)
            ->with(['equipoOrigen', 'equipoDestino', 'deposito'])
            ->latest('updated_at')
            ->limit(self::LIMITE)
            ->get();
    }

    /** Cuántas piezas hay en cada estado (para el encabezado y la pestaña). */
    #[Computed]
    public function resumen(): array
    {
        if (! $this->componenteId) {
            return [];
        }

        return PiezaExtraida::where('componente_almacen_id', $this->componenteId)
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /** Kardex de cantidades: entradas, salidas y a qué caso fue cada salida. */
    #[Computed]
    public function usos()
    {
        if (! $this->componenteId) {
            return collect();
        }

        return MovimientoComponente::with(['mantenimiento.equipo', 'registradoPor'])
            ->where('componente_id', $this->componenteId)
            ->latest()
            ->orderByDesc('id')
            ->limit(self::LIMITE)
            ->get();
    }

    /**
     * Le pone serial a UNA unidad que está en stock sin identificar (stock
     * de antes de la trazabilidad, o que entró por cantidad). El stock no
     * cambia: esa unidad pasa a ser una pieza con su serial y su recorrido.
     */
    public function asignarSerial(): void
    {
        $componente = $this->componente;
        abort_unless($componente, 404);
        $this->authorize('registrarMovimiento', $componente);

        $serial = trim($this->serialNuevo);
        if ($serial === '' || mb_strlen($serial) > 100) {
            $this->addError('serialNuevo', 'Escribe el serial.');
            return;
        }
        if ($componente->esAGranel() || $componente->unidadesSinIdentidad() < 1) {
            $this->addError('serialNuevo', 'No hay unidades sin serial en esta variante.');
            return;
        }
        if (PiezaExtraida::where('empresa_id', $componente->empresa_id)->where('identificador', $serial)->exists()) {
            $this->addError('serialNuevo', "Ya hay otra pieza con el serial «{$serial}».");
            return;
        }

        $valores  = $componente->valoresFicha();
        $atributo = $valores ? $componente->familia?->atributoRepresentativo() : null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($componente, $serial, $valores, $atributo) {
            $pieza = PiezaExtraida::create([
                'empresa_id'            => $componente->empresa_id,
                'atributo_id'           => $atributo?->id,
                'valor_extraido'        => $valores ?: null,
                'reutilizable'          => true,
                'condicion'             => PiezaExtraida::CONDICION_NUEVO,
                'identificador'         => $serial,
                'estado'                => PiezaExtraida::ESTADO_EN_ALMACEN,
                'componente_almacen_id' => $componente->id,
                'motivo'                => PiezaExtraida::MOTIVO_REGISTRO_MANUAL,
                'extraido_por'          => \Illuminate\Support\Facades\Auth::id(),
            ]);
            $pieza->movimientos()->create([
                'tipo'           => \App\Models\PiezaExtraidaMovimiento::TIPO_INGRESO_ALMACEN,
                'registrado_por' => \Illuminate\Support\Facades\Auth::id(),
                'observaciones'  => 'Ya estaba en el almacén sin serial; se le puso su serial.',
            ]);
        });

        $this->serialNuevo = '';
        $this->resetValidation();
        unset($this->piezas, $this->todasLasPiezas, $this->resumen);
        $this->dispatch('stockActualizado');
        $this->dispatch('toast', type: 'success', message: "Unidad identificada con el serial {$serial}.");
    }

    /** A granel: devoluciones, retiros de equipos y daños, con su destino. */
    #[Computed]
    public function retirosGranel()
    {
        return $this->componenteId
            ? \App\Models\RetiroGranel::with(['equipo:id,codigo_interno', 'mantenimiento:id', 'deposito:id,nombre', 'registradoPor:id,name'])
                ->where('componente_id', $this->componenteId)->latest()->limit(self::LIMITE)->get()
            : collect();
    }

    /** Abre la ficha de una pieza de ESTA variante (el id llega del navegador). */
    public function verPieza(int $piezaId): void
    {
        $existe = PiezaExtraida::where('componente_almacen_id', $this->componenteId)->whereKey($piezaId)->exists();

        if (! $existe) {
            return;
        }

        $this->close();
        $this->dispatch('openPiezaFicha', piezaId: $piezaId);
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['componenteId', 'pestana']);
    }

    public function render()
    {
        return view('livewire.almacen.componente-piezas-modal');
    }
}
