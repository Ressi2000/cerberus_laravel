<?php

namespace App\Livewire\Configuracion\Atributos;

use App\Models\AtributoEquipo;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class AtributoDeleteModal extends Component
{
    public bool            $open         = false;
    public ?AtributoEquipo $atributo     = null;
    public int             $totalValores = 0;

    #[On('openAtributoEliminar')]
    public function abrir(int $id): void
    {
        $this->atributo     = AtributoEquipo::with('categoria')->withCount('valores')->findOrFail($id);
        $this->totalValores = $this->atributo->valores_count;
        $this->open         = true;
    }

    public function eliminar(): void
    {
        if (! $this->atributo) return;

        if ($this->totalValores > 0) {
            $this->dispatch(
                'toast',
                type: 'error',
                message: "No se puede eliminar: hay {$this->totalValores} valor(es) registrado(s) en equipos."
            );
            $this->close();
            return;
        }

        try {
            $nombre = $this->atributo->nombre;
            $this->atributo->delete();
            $this->close();
            $this->dispatch('atributoEliminado');
            $this->dispatch('toast', type: 'success', message: "Atributo «{$nombre}» eliminado.");
        } catch (\Exception $e) {
            Log::error('AtributoDeleteModal@eliminar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al eliminar el atributo.');
            $this->close();
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['atributo', 'totalValores']);
    }

    public function render()
    {
        return view('livewire.configuracion.atributos.atributo-delete-modal');
    }
}
