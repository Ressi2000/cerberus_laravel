<?php

namespace App\Livewire\Prestamos;

use App\Models\Prestamo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * PrestamoAreaViewModal
 *
 * Modal de detalle para préstamos a áreas comunes.
 * Análogo a App\Livewire\Asignaciones\AreaViewModal.
 *
 * Escucha: openPrestamoAreaView con { id: int }   ← ID del Prestamo (no de usuario)
 */
class PrestamoAreaViewModal extends Component
{
    public bool $open       = false;
    public ?int $prestamoId = null;

    #[On('openPrestamoAreaView')]
    public function abrir(int $id): void
    {
        $this->prestamoId = $id;
        $this->open       = true;
    }

    public function cerrar(): void
    {
        $this->open       = false;
        $this->prestamoId = null;
    }

    #[Computed]
    public function prestamo(): ?Prestamo
    {
        if (! $this->prestamoId) return null;

        return Prestamo::with([
            'empresa',
            'areaEmpresa',
            'areaDepartamento',
            'areaResponsable.cargo',
            'analista',
            'items' => fn ($q) => $q->whereNull('equipo_padre_id')->with([
                'equipo.categoria',
                'equipo.atributosActuales.atributo',
                'hijos.equipo.categoria',
                'devueltoPor',
            ]),
        ])->find($this->prestamoId);
    }

    #[Computed]
    public function itemsActivos()
    {
        return $this->prestamo?->items
            ->filter(fn ($item) => ! $item->devuelto)
            ?? collect();
    }

    #[Computed]
    public function itemsDevueltos()
    {
        return $this->prestamo?->items
            ->filter(fn ($item) => $item->devuelto)
            ?? collect();
    }

    public function render()
    {
        return view('livewire.prestamos.prestamo-area-view-modal');
    }
}
