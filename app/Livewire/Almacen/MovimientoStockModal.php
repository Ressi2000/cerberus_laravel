<?php

namespace App\Livewire\Almacen;

use App\Models\ComponenteAlmacen;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Registrar una entrada o salida manual de stock (ej. compra, ajuste de
 * inventario) y mostrar el kardex reciente del componente. El consumo desde
 * un mantenimiento NO pasa por aquí — eso lo maneja Mantenimiento::pedirComponente().
 */
class MovimientoStockModal extends Component
{
    public bool   $open         = false;
    public ?int   $componenteId = null;
    public string $tipo         = 'Entrada';
    public ?int   $cantidad     = null;
    public string $motivo       = '';
    public string $observaciones = '';

    #[On('openMovimientoStock')]
    public function abrir(int $componenteId): void
    {
        $componente = ComponenteAlmacen::findOrFail($componenteId);
        $this->authorize('registrarMovimiento', $componente);

        $this->componenteId = $componenteId;
        $this->reset(['cantidad', 'motivo', 'observaciones']);
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

    protected function rules(): array
    {
        return [
            'tipo'          => 'required|in:Entrada,Salida',
            'cantidad'      => 'required|integer|min:1',
            'motivo'        => 'nullable|string|max:150',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    protected function messages(): array
    {
        return [
            'cantidad.required' => 'Indica la cantidad.',
            'cantidad.min'      => 'La cantidad debe ser al menos 1.',
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
            } else {
                $componente->registrarSalida($this->cantidad, Auth::user(), $this->motivo ?: 'Ajuste manual');
            }

            $msg = "Movimiento de stock registrado para «{$componente->nombre}».";
            $this->close();
            $this->dispatch('stockActualizado');
            $this->dispatch('toast', type: 'success', message: $msg);
        } catch (\Exception $e) {
            Log::error('MovimientoStockModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al registrar el movimiento.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['componenteId', 'tipo', 'cantidad', 'motivo', 'observaciones']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.almacen.movimiento-stock-modal');
    }
}
