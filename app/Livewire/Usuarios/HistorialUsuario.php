<?php

namespace App\Livewire\Usuarios;

use App\Models\Auditoria;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class HistorialUsuario extends Component
{
    use WithPagination;

    public User $usuario;

    public string $accion    = '';
    public string $fecha_desde = '';
    public string $fecha_hasta = '';

    public function mount(User $usuario): void
    {
        $this->authorize('view', $usuario);
        $this->usuario = $usuario->load(['empresaNomina', 'ubicacion', 'departamento', 'cargo']);
    }

    public function updated($property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['accion', 'fecha_desde', 'fecha_hasta']);
        $this->resetPage();
    }

    public function render()
    {
        $historial = Auditoria::with('usuario')
            ->where('tabla', 'users')
            ->where('registro_id', $this->usuario->id)
            ->when($this->accion, fn($q) => $q->where('accion', $this->accion))
            ->when($this->fecha_desde, fn($q) => $q->whereDate('created_at', '>=', $this->fecha_desde))
            ->when($this->fecha_hasta, fn($q) => $q->whereDate('created_at', '<=', $this->fecha_hasta))
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.usuarios.historial-usuario', [
            'historial' => $historial,
        ]);
    }
}
