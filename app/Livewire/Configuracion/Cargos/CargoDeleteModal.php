<?php

namespace App\Livewire\Configuracion\Cargos;

use App\Models\Cargo;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class CargoDeleteModal extends Component
{
    public bool   $open          = false;
    public ?Cargo $cargo         = null;
    public int    $totalUsuarios = 0;

    #[On('openCargoEliminar')]
    public function abrir(int $id): void
    {
        $this->cargo         = Cargo::withCount([
            'usuarios' => fn($q) => $q->where('estado', 'Activo'),
        ])->findOrFail($id);

        $this->totalUsuarios = $this->cargo->usuarios_count;
        $this->open          = true;
    }

    public function desactivar(): void
    {
        if (! $this->cargo) return;

        if ($this->totalUsuarios > 0) {
            $this->dispatch('toast', type: 'error',
                message: "No se puede desactivar: tiene {$this->totalUsuarios} usuario(s) activo(s) con este cargo.");
            $this->close();
            return;
        }

        try {
            $nombre = $this->cargo->nombre;
            $this->cargo->update(['activo' => false]);

            $this->close();
            $this->dispatch('cargoEliminado');
            $this->dispatch('toast', type: 'success', message: "Cargo «{$nombre}» desactivado.");
        } catch (\Exception $e) {
            Log::error('CargoDeleteModal@desactivar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al desactivar el cargo.');
            $this->close();
        }
    }

    #[On('reactivarCargo')]
    public function reactivar(int $id): void
    {
        try {
            $cargo = Cargo::findOrFail($id);
            $cargo->update(['activo' => true]);

            $this->dispatch('cargoEliminado');
            $this->dispatch('toast', type: 'success', message: "Cargo «{$cargo->nombre}» reactivado.");
        } catch (\Exception $e) {
            Log::error('CargoDeleteModal@reactivar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al reactivar el cargo.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['cargo', 'totalUsuarios']);
    }

    public function render()
    {
        return view('livewire.configuracion.cargos.cargo-delete-modal');
    }
}