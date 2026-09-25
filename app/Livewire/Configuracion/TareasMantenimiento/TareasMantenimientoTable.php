<?php

namespace App\Livewire\Configuracion\TareasMantenimiento;

use App\Models\TareaMantenimientoCatalogo;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class TareasMantenimientoTable extends Component
{
    public string $search            = '';
    public bool   $mostrar_inactivas = false;

    public function mount(): void
    {
        $this->authorize('viewAny', TareaMantenimientoCatalogo::class);
    }

    #[On('tareaGuardada')]
    public function refrescar(): void
    {
        //
    }

    public function reactivar(int $id): void
    {
        $tarea = TareaMantenimientoCatalogo::findOrFail($id);
        $this->authorize('update', $tarea);
        $tarea->update(['activo' => true]);
        $this->dispatch('toast', type: 'success', message: "Tarea «{$tarea->nombre}» reactivada.");
    }

    public function desactivar(int $id): void
    {
        $tarea = TareaMantenimientoCatalogo::findOrFail($id);
        $this->authorize('delete', $tarea);
        $tarea->update(['activo' => false]);
        $this->dispatch('toast', type: 'success', message: "Tarea «{$tarea->nombre}» desactivada.");
    }

    #[Computed]
    public function total(): int
    {
        return TareaMantenimientoCatalogo::where('activo', true)->count();
    }

    #[Computed]
    public function totalInactivas(): int
    {
        return TareaMantenimientoCatalogo::where('activo', false)->count();
    }

    #[Computed]
    public function tareas()
    {
        return TareaMantenimientoCatalogo::when(! $this->mostrar_inactivas, fn ($q) => $q->where('activo', true))
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->ordenadas()
            ->get();
    }

    public function render()
    {
        return view('livewire.configuracion.tareas-mantenimiento.tareas-mantenimiento-table');
    }
}
