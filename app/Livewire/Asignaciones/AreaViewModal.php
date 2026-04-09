<?php

namespace App\Livewire\Asignaciones;

use App\Models\Asignacion;
use App\Models\AsignacionItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * AreaViewModal
 *
 * Modal de detalle para asignaciones a áreas comunes.
 * Muestra la información del área y los equipos activos asignados.
 *
 * Escucha: openAreaView con { id: int }   ← ID de la Asignación (no del usuario)
 *
 * Separado de AsignacionViewModal (que opera sobre usuario_id) porque
 * las áreas no tienen usuario receptor — tienen empresa + departamento + responsable.
 */
class AreaViewModal extends Component
{
    public bool $open         = false;
    public ?int $asignacionId = null;

    // ─────────────────────────────────────────────────────────────────────────

    #[On('openAreaView')]
    public function abrir(int $id): void
    {
        $this->asignacionId = $id;
        $this->open         = true;
    }

    public function cerrar(): void
    {
        $this->open         = false;
        $this->asignacionId = null;
    }

    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function asignacion(): ?Asignacion
    {
        if (! $this->asignacionId) return null;

        return Asignacion::with([
            'empresa',
            'areaEmpresa',
            'areaDepartamento',
            'areaResponsable.cargo',
            'areaResponsable.departamento',
            'analista',
            'items' => fn ($q) => $q->whereNull('equipo_padre_id')->with([
                'equipo.categoria',
                'equipo.estado',
                'equipo.atributosActuales.atributo',
                'hijos.equipo.categoria',
            ]),
        ])->find($this->asignacionId);
    }

    #[Computed]
    public function itemsActivos()
    {
        return $this->asignacion?->items
            ->filter(fn ($item) => ! $item->devuelto)
            ?? collect();
    }

    #[Computed]
    public function itemsDevueltos()
    {
        return $this->asignacion?->items
            ->filter(fn ($item) => $item->devuelto)
            ?? collect();
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.asignaciones.area-view-modal');
    }
}