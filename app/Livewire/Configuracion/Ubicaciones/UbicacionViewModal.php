<?php

namespace App\Livewire\Configuracion\Ubicaciones;

use App\Models\Ubicacion;
use Livewire\Attributes\On;
use Livewire\Component;

class UbicacionViewModal extends Component
{
    public bool       $open      = false;
    public ?Ubicacion $ubicacion = null;

    #[On('openUbicacionVer')]
    public function abrir(int $id): void
    {
        $this->ubicacion = Ubicacion::with('empresa')
            ->withCount(['usuarios', 'equipos'])
            ->findOrFail($id);

        $this->open = true;
    }

    public function close(): void
    {
        $this->open     = false;
        $this->ubicacion = null;
    }

    public function render()
    {
        return view('livewire.configuracion.ubicaciones.ubicacion-view-modal');
    }
}