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
        $this->cargo         = Cargo::withCount('usuarios')->findOrFail($id);
        $this->totalUsuarios = $this->cargo->usuarios_count;
        $this->open          = true;
    }

    public function eliminar(): void
    {
        if (! $this->cargo) return;

        if ($this->totalUsuarios > 0) {
            $this->dispatch(
                'toast',
                type: 'error',
                message: "No se puede eliminar: tiene {$this->totalUsuarios} usuario(s) asociado(s)."
            );
            $this->close();
            return;
        }

        try {
            $nombre = $this->cargo->nombre;
            $this->cargo->delete(); // SoftDelete

            $this->close();
            $this->dispatch('cargoEliminado');
            $this->dispatch('toast', type: 'success', message: "Cargo «{$nombre}» eliminado.");

        } catch (\Exception $e) {
            Log::error('CargoDeleteModal@eliminar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al eliminar el cargo.');
            $this->close();
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