<?php

namespace App\Livewire\Almacen;

use App\Models\ComponenteAlmacen;
use App\Models\Deposito;
use App\Models\PiezaExtraida;
use App\Services\DescarteComponenteService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Registrar una SALIDA manual de stock (pérdida, corrección de inventario o
 * una unidad que se dañó) y ver el kardex reciente del componente. Las
 * entradas se registran con RegistrarEntradaModal; el uso en un caso, desde
 * el caso ("Usar del almacén").
 *
 * Una salida puede además marcarse como "dañado" — ver DescarteComponenteService:
 * exige indicar un depósito destino.
 *
 * En toda salida, si el componente tiene piezas con identidad en el almacén
 * y la cantidad supera sus unidades sin identidad, hay que marcar cuáles
 * piezas salen (ver ComponenteAlmacen::registrarSalida()) — así el stock y
 * las piezas en almacén siguen cuadrando.
 */
class MovimientoStockModal extends Component
{
    public bool   $open         = false;
    // Locked: el id no se puede cambiar desde el navegador.
    #[Locked]
    public ?int   $componenteId = null;
    public ?int   $cantidad     = null;
    public string $motivo       = '';
    public string $observaciones = '';

    public bool   $danado      = false;
    public ?int   $depositoId  = null;

    /** @var array<int, int|string> Piezas con identidad que salen en una Salida. */
    public array  $piezaIds    = [];

    #[On('openMovimientoStock')]
    public function abrir(int $componenteId): void
    {
        $componente = ComponenteAlmacen::findOrFail($componenteId);
        $this->authorize('registrarMovimiento', $componente);

        $this->componenteId = $componenteId;
        $this->reset(['cantidad', 'motivo', 'observaciones', 'danado', 'depositoId', 'piezaIds']);
        $this->resetValidation();
        $this->open = true;
    }

    #[Computed]
    public function componente(): ?ComponenteAlmacen
    {
        return $this->componenteId ? ComponenteAlmacen::find($this->componenteId) : null;
    }

    /** Historial de uso: en qué mantenimientos/reparaciones se consumió este componente. */
    #[Computed]
    public function usosEnMantenimiento()
    {
        if (! $this->componenteId) {
            return collect();
        }

        return \App\Models\MantenimientoComponente::with(['mantenimiento.equipo', 'entregadoPor'])
            ->where('componente_id', $this->componenteId)
            ->where('estado', 'Entregado')
            ->latest('fecha_entrega')
            ->limit(10)
            ->get();
    }

    /** Piezas con identidad de este componente que siguen en Almacén (para elegir cuáles salen). */
    #[Computed]
    public function piezasEnAlmacen()
    {
        if (! $this->componenteId) {
            return collect();
        }

        return PiezaExtraida::with(['atributo', 'componenteAlmacen', 'equipoOrigen'])
            ->where('componente_almacen_id', $this->componenteId)
            ->where('estado', PiezaExtraida::ESTADO_EN_ALMACEN)
            ->oldest()
            ->get();
    }

    /** Unidades del stock sin pieza individual: salen sin tener que elegir nada. */
    #[Computed]
    public function unidadesSinIdentidad(): int
    {
        return $this->componente?->unidadesSinIdentidad() ?? 0;
    }

    #[Computed]
    public function depositosDisponibles()
    {
        $componente = $this->componente;

        return $componente
            ? Deposito::where('empresa_id', $componente->empresa_id)->activos()->orderBy('nombre')->get()
            : collect();
    }

    protected function rules(): array
    {
        $rules = [
            'cantidad'      => 'required|integer|min:1',
            'motivo'        => 'nullable|string|max:150',
            'observaciones' => 'nullable|string|max:500',
            'piezaIds'      => 'array',
            'piezaIds.*'    => 'integer',
        ];

        if ($this->danado) {
            $rules['depositoId'] = 'required|exists:depositos,id';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'cantidad.required'      => 'Indica la cantidad.',
            'cantidad.min'           => 'La cantidad debe ser al menos 1.',
            'depositoId.required'    => 'Selecciona en qué depósito queda la unidad dañada.',
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        $componente = $this->componente;

        if (! $componente) {
            $this->close();
            return;
        }

        $this->authorize('registrarMovimiento', $componente);

        if ($componente->stock_actual < $this->cantidad) {
            $this->addError('cantidad', "No hay stock suficiente. Stock actual: {$componente->stock_actual}.");
            return;
        }

        // Solo piezas de ESTE componente (los ids llegan del navegador).
        $piezas = collect();
        if (! empty($this->piezaIds)) {
            $ids    = collect($this->piezaIds)->map(fn ($id) => (int) $id)->unique();
            $piezas = PiezaExtraida::where('componente_almacen_id', $componente->id)->whereIn('id', $ids)->get();

            if ($piezas->count() !== $ids->count()) {
                $this->addError('piezaIds', 'Alguna de las piezas elegidas no pertenece a este componente.');
                return;
            }

            if ($piezas->count() > $this->cantidad) {
                $this->addError('piezaIds', "Marcaste {$piezas->count()} piezas pero la cantidad es {$this->cantidad}.");
                return;
            }
        }

        try {
            if ($this->danado) {
                // Solo depósitos activos de la empresa del componente.
                $deposito = Deposito::activos()
                    ->where('empresa_id', $componente->empresa_id)
                    ->find($this->depositoId);

                if (! $deposito) {
                    $this->addError('depositoId', 'Ese depósito no está disponible para este componente.');
                    return;
                }

                app(DescarteComponenteService::class)->marcarDanado(
                    $componente, $this->cantidad, $deposito, Auth::user(), $piezas, $this->observaciones ?: null,
                );
            } else {
                $componente->registrarAjusteSalida(
                    $this->cantidad, Auth::user(), $this->motivo ?: null, $this->observaciones ?: null, $piezas,
                );
            }

            $msg = "Salida registrada para «{$componente->nombre}».";
            $this->close();
            $this->dispatch('stockActualizado');
            $this->dispatch('toast', type: 'success', message: $msg);
        } catch (\InvalidArgumentException $e) {
            // Casi siempre es "indica cuáles piezas salen": se muestra junto a la lista.
            $this->addError('piezaIds', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('MovimientoStockModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al registrar el movimiento.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['componenteId', 'cantidad', 'motivo', 'observaciones', 'danado', 'depositoId', 'piezaIds']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.almacen.movimiento-stock-modal');
    }
}
