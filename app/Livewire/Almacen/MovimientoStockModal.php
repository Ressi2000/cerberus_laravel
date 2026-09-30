<?php

namespace App\Livewire\Almacen;

use App\Models\ComponenteAlmacen;
use App\Models\Deposito;
use App\Models\PiezaExtraida;
use App\Services\DescarteComponenteService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Registrar una entrada o salida manual de stock (ej. compra, ajuste de
 * inventario) y mostrar el kardex reciente del componente. El consumo desde
 * un mantenimiento NO pasa por aquí — eso lo maneja Mantenimiento::pedirComponente().
 *
 * Una salida puede además marcarse como "dañado" — ver DescarteComponenteService:
 * exige indicar un depósito destino y, si hay unidades trazadas (rescatadas
 * vía ExtraccionPiezaService) en Almacén, permite vincular una específica
 * para conservar su historial completo en vez de perderla en el conteo.
 */
class MovimientoStockModal extends Component
{
    public bool   $open         = false;
    public ?int   $componenteId = null;
    public string $tipo         = 'Entrada';
    public ?int   $cantidad     = null;
    public string $motivo       = '';
    public string $observaciones = '';

    public bool   $danado      = false;
    public ?int   $depositoId  = null;
    public ?int   $piezaId     = null;

    #[On('openMovimientoStock')]
    public function abrir(int $componenteId): void
    {
        $componente = ComponenteAlmacen::findOrFail($componenteId);
        $this->authorize('registrarMovimiento', $componente);

        $this->componenteId = $componenteId;
        $this->reset(['cantidad', 'motivo', 'observaciones', 'danado', 'depositoId', 'piezaId']);
        $this->tipo = 'Entrada';
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

    /** Unidades trazadas de este componente que siguen en Almacén (para vincular al dar de baja una dañada). */
    #[Computed]
    public function piezasEnAlmacen()
    {
        if (! $this->componenteId) {
            return collect();
        }

        return PiezaExtraida::with('atributo')
            ->where('componente_almacen_id', $this->componenteId)
            ->where('estado', PiezaExtraida::ESTADO_EN_ALMACEN)
            ->get();
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

        if ($this->tipo === 'Salida' && $this->danado) {
            $rules['depositoId'] = 'required|exists:depositos,id';
            if ($this->piezaId) {
                $rules['cantidad'] = 'required|integer|in:1';
            }
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'cantidad.required'  => 'Indica la cantidad.',
            'cantidad.min'       => 'La cantidad debe ser al menos 1.',
            'cantidad.in'        => 'Solo se puede vincular una pieza trazada específica cuando la cantidad es 1.',
            'depositoId.required'=> 'Selecciona en qué depósito queda la unidad dañada.',
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

        try {
            if ($this->tipo === 'Entrada') {
                $componente->registrarEntrada(
                    $this->cantidad,
                    Auth::user(),
                    $this->motivo ?: 'Entrada manual',
                    $this->observaciones ?: null
                );
            } elseif ($this->danado) {
                $deposito = Deposito::findOrFail($this->depositoId);
                $pieza    = $this->piezaId ? PiezaExtraida::find($this->piezaId) : null;

                app(DescarteComponenteService::class)->marcarDanado(
                    $componente, $this->cantidad, $deposito, Auth::user(), $pieza, $this->observaciones ?: null,
                );
            } else {
                $componente->registrarSalida($this->cantidad, Auth::user(), $this->motivo ?: 'Ajuste manual');
            }

            $msg = "Movimiento de stock registrado para «{$componente->nombre}».";
            $this->close();
            $this->dispatch('stockActualizado');
            $this->dispatch('toast', type: 'success', message: $msg);
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        } catch (\Exception $e) {
            Log::error('MovimientoStockModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al registrar el movimiento.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['componenteId', 'tipo', 'cantidad', 'motivo', 'observaciones', 'danado', 'depositoId', 'piezaId']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.almacen.movimiento-stock-modal');
    }
}
