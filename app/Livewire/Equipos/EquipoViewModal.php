<?php

namespace App\Livewire\Equipos;

use App\Models\AsignacionItem;
use App\Models\Equipo;
use Livewire\Component;
use Livewire\Attributes\On;

class EquipoViewModal extends Component
{
    public bool $open = false;
    public ?Equipo $equipo = null;
    public ?AsignacionItem $asignacionActiva = null;

    #[On('openEquipoView')]
    public function openEquipoView(int $id): void
    {
        $this->equipo = Equipo::with([
            'categoria',
            'estado',
            'ubicacion',
            'atributosActuales.atributo',
            'grupoInstancias.atributo',
        ])->findOrFail($id);

        // ── Cargar asignación activa (si existe) ──────────────────────────────
        $this->asignacionActiva = AsignacionItem::with([
            'asignacion.usuario.cargo',
            'asignacion.usuario.ubicacion',
            'asignacion.usuario.departamento',
            'asignacion.areaDepartamento',
            'asignacion.areaEmpresa',
            'asignacion.areaResponsable',
            'asignacion.analista',
        ])
            ->where('equipo_id', $id)
            ->where('devuelto', false)
            ->whereHas('asignacion', fn($q) => $q->where('estado', 'Activa'))
            ->latest()
            ->first();

        $this->open = true;
    }

    public function close(): void
    {
        $this->reset(['open', 'equipo', 'asignacionActiva']);
    }

    public function render()
    {
        return view('livewire.equipos.equipo-view-modal');
    }
}
