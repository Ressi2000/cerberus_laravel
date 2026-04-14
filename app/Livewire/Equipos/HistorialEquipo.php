<?php

namespace App\Livewire\Equipos;

use App\Models\Equipo;
use App\Models\AtributoEquipo;
use Livewire\Component;
use Livewire\WithPagination;

class HistorialEquipo extends Component
{
    use WithPagination;

    public Equipo $equipo;

    public string $atributo_id = '';
    public string $fecha_desde = '';
    public string $fecha_hasta = '';

    public function mount(Equipo $equipo): void
    {
        $this->authorize('view', $equipo);
        $this->equipo = $equipo->load(['categoria', 'estado', 'ubicacion', 'empresa']);
    }

    public function updated($property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['atributo_id', 'fecha_desde', 'fecha_hasta']);
        $this->resetPage();
    }

    public function render()
    {
        // Atributos de la categoría para el filtro
        $atributos = AtributoEquipo::where('categoria_id', $this->equipo->categoria_id)
            ->orderBy('orden')
            ->pluck('nombre', 'id');

        // Historial completo con filtros
        $historial = $this->equipo
            ->atributosHistorico()
            ->with(['atributo', 'usuario'])
            ->when($this->atributo_id, fn($q) => $q->where('atributo_id', $this->atributo_id))
            ->when($this->fecha_desde, fn($q) => $q->whereDate('created_at', '>=', $this->fecha_desde))
            ->when($this->fecha_hasta, fn($q) => $q->whereDate('created_at', '<=', $this->fecha_hasta))
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.equipos.historial-equipo', [
            'historial' => $historial,
            'atributos' => $atributos,
        ]);
    }
}
