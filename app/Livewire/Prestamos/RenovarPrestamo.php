<?php

namespace App\Livewire\Prestamos;

use App\Models\Prestamo;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class RenovarPrestamo extends Component
{
    public bool   $abierto    = false;
    public int    $prestamoId = 0;
    public string $nuevaFecha = '';
    public string $receptorNombre = '';
    public string $estadoActual   = '';

    // ─────────────────────────────────────────────────────────────────────────

    #[On('abrir-renovar')]
    public function abrir(int $prestamoId): void
    {
        $prestamo = Prestamo::with(['usuario', 'areaDepartamento', 'areaEmpresa'])->findOrFail($prestamoId);
        $this->authorize('renovar', $prestamo);

        $this->prestamoId     = $prestamoId;
        $this->nuevaFecha     = $prestamo->fecha_devolucion_esperada?->format('Y-m-d') ?? '';
        $this->receptorNombre = $prestamo->receptorNombre();
        $this->estadoActual   = $prestamo->estado;
        $this->abierto        = true;
        $this->resetErrorBag();
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->reset(['prestamoId', 'nuevaFecha', 'receptorNombre', 'estadoActual']);
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function confirmar(): void
    {
        $this->validate([
            'nuevaFecha' => 'required|date|after_or_equal:today',
        ], [
            'nuevaFecha.required'         => 'Ingresa una fecha de devolución.',
            'nuevaFecha.after_or_equal'   => 'La nueva fecha debe ser hoy o posterior.',
        ]);

        $prestamo = Prestamo::findOrFail($this->prestamoId);
        $this->authorize('renovar', $prestamo);

        try {
            $prestamo->renovar($this->nuevaFecha);

            session()->flash('success', 'Fecha de devolución actualizada correctamente.');
            $this->dispatch('prestamo-renovado');
            $this->cerrar();

        } catch (\Exception $e) {
            Log::error('RenovarPrestamo@confirmar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al renovar el préstamo.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.prestamos.renovar-prestamo');
    }
}
