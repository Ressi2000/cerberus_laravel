<?php

namespace App\Livewire\Configuracion\Estados;

use App\Models\EstadoEquipo;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class EstadoViewModal extends Component
{
    public bool          $open   = false;
    public ?EstadoEquipo $estado = null;

    #[On('openEstadoVer')]
    public function abrir(int $id): void
    {
        $this->estado = EstadoEquipo::withCount('equipos')->findOrFail($id);
        $this->open   = true;
    }

    public function close(): void
    {
        $this->open = false;
        $this->estado = null;
    }
    public function render()
    {
        return view('livewire.configuracion.estados.estado-view-modal');
    }
}
