<?php

namespace App\Livewire\Traslados;

use App\Models\Traslado;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class TrasladoDeleteModal extends Component
{
    public bool $open        = false;
    public ?int $trasladoId  = null;
    public ?Traslado $traslado = null;

    #[On('eliminarTraslado')]
    public function abrir(int $id): void
    {
        $this->authorize('delete', Traslado::findOrFail($id));

        $this->traslado   = Traslado::with(['ubicacionOrigen', 'ubicacionDestino'])->findOrFail($id);
        $this->trasladoId = $id;
        $this->open       = true;
    }

    public function confirmar(): void
    {
        if (! $this->traslado) return;

        $this->authorize('delete', $this->traslado);

        try {
            $numero = $this->traslado->numero;
            $this->traslado->delete();

            $this->cerrar();
            $this->dispatch('toast', type: 'success', message: "Traslado {$numero} eliminado correctamente.");
            $this->dispatch('trasladoEliminado');
        } catch (\Exception $e) {
            Log::error('TrasladoDeleteModal@confirmar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Ocurrió un error al eliminar el traslado.');
        }
    }

    public function cerrar(): void
    {
        $this->reset(['open', 'traslado', 'trasladoId']);
    }

    public function render()
    {
        return view('livewire.traslados.traslado-delete-modal');
    }
}
