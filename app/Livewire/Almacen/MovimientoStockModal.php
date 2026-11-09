<?php

namespace App\Livewire\Almacen;

use App\Models\ComponenteAlmacen;
use App\Models\Deposito;
use App\Models\Equipo;
use App\Models\PiezaExtraida;
use App\Services\DescarteComponenteService;
use App\Services\PiezaManualService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Registrar una entrada o salida manual de stock (ej. compra, ajuste de
 * inventario) y mostrar el kardex reciente del componente. El consumo desde
 * un mantenimiento NO pasa por aquí — eso lo maneja Mantenimiento::pedirComponente().
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
    public string $tipo         = 'Entrada';
    public ?int   $cantidad     = null;
    public string $motivo       = '';
    public string $observaciones = '';

    public bool   $danado      = false;
    public ?int   $depositoId  = null;

    /** @var array<int, int|string> Piezas con identidad que salen en una Salida. */
    public array  $piezaIds    = [];

    // ── Identidad de la(s) unidad(es) que entran (ver PiezaManualService) ────
    public string $condicion      = PiezaExtraida::CONDICION_NUEVO;
    public ?int   $equipoOrigenId = null;
    public string $identificador  = '';

    #[On('openMovimientoStock')]
    public function abrir(int $componenteId): void
    {
        $componente = ComponenteAlmacen::findOrFail($componenteId);
        $this->authorize('registrarMovimiento', $componente);

        $this->componenteId = $componenteId;
        $this->reset(['cantidad', 'motivo', 'observaciones', 'danado', 'depositoId', 'piezaIds', 'equipoOrigenId', 'identificador']);
        $this->tipo = 'Entrada';
        $this->condicion = PiezaExtraida::CONDICION_NUEVO;
        $this->resetValidation();
        $this->open = true;
    }

    /** Equipos de la misma empresa del componente — de dónde pudo salir una pieza reutilizada. */
    #[Computed]
    public function equiposOpciones()
    {
        $componente = $this->componente;

        return $componente
            ? Equipo::where('empresa_id', $componente->empresa_id)->orderBy('codigo_interno')->pluck('codigo_interno', 'id')
            : collect();
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
            'tipo'          => 'required|in:Entrada,Salida',
            'cantidad'      => 'required|integer|min:1',
            'motivo'        => 'nullable|string|max:150',
            'observaciones' => 'nullable|string|max:500',
        ];

        if ($this->tipo === 'Salida') {
            $rules['piezaIds']   = 'array';
            $rules['piezaIds.*'] = 'integer';

            if ($this->danado) {
                $rules['depositoId'] = 'required|exists:depositos,id';
            }
        }

        if ($this->tipo === 'Entrada') {
            $rules['condicion'] = 'required|in:' . PiezaExtraida::CONDICION_NUEVO . ',' . PiezaExtraida::CONDICION_REUTILIZADO;
            if ($this->condicion === PiezaExtraida::CONDICION_REUTILIZADO) {
                $rules['equipoOrigenId'] = 'required|exists:equipos,id';
            }
            $rules['identificador'] = 'nullable|string|max:100';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'cantidad.required'      => 'Indica la cantidad.',
            'cantidad.min'           => 'La cantidad debe ser al menos 1.',
            'depositoId.required'    => 'Selecciona en qué depósito queda la unidad dañada.',
            'equipoOrigenId.required'=> 'Indica de qué equipo salió esta pieza reutilizada.',
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

        if ($this->tipo === 'Salida' && $componente->stock_actual < $this->cantidad) {
            $this->addError('cantidad', "No hay stock suficiente. Stock actual: {$componente->stock_actual}.");
            return;
        }

        // Solo piezas de ESTE componente (los ids llegan del navegador).
        $piezas = collect();
        if ($this->tipo === 'Salida' && ! empty($this->piezaIds)) {
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
            if ($this->tipo === 'Entrada') {
                // Solo equipos de la empresa del componente.
                $equipoOrigen = null;
                if ($this->equipoOrigenId) {
                    $equipoOrigen = Equipo::where('empresa_id', $componente->empresa_id)->find($this->equipoOrigenId);

                    if (! $equipoOrigen) {
                        $this->addError('equipoOrigenId', 'Ese equipo no pertenece a la empresa del componente.');
                        return;
                    }
                }

                app(PiezaManualService::class)->registrar(
                    $componente,
                    $this->cantidad,
                    $this->condicion,
                    Auth::user(),
                    equipoOrigen: $equipoOrigen,
                    identificador: $this->identificador ?: null,
                    observaciones: $this->observaciones ?: null,
                );
            } elseif ($this->danado) {
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

            $msg = "Movimiento de stock registrado para «{$componente->nombre}».";
            $this->close();
            $this->dispatch('stockActualizado');
            $this->dispatch('toast', type: 'success', message: $msg);
        } catch (\InvalidArgumentException $e) {
            // En una salida casi siempre es "indica cuáles piezas salen":
            // se muestra junto a la lista de piezas.
            if ($this->tipo === 'Salida') {
                $this->addError('piezaIds', $e->getMessage());
            } else {
                $this->dispatch('toast', type: 'error', message: $e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('MovimientoStockModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al registrar el movimiento.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['componenteId', 'tipo', 'cantidad', 'motivo', 'observaciones', 'danado', 'depositoId', 'piezaIds', 'condicion', 'equipoOrigenId', 'identificador']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.almacen.movimiento-stock-modal');
    }
}
