<?php

namespace App\Livewire\Configuracion\Cargos;

use App\Models\Cargo;
use Livewire\Attributes\On;
use Livewire\Component;

class CargoViewModal extends Component
{
    public bool   $open  = false;
    public ?Cargo $cargo = null;

    #[On('openCargoVer')]
    public function abrir(int $id): void
    {
        $this->cargo = Cargo::with(['empresa', 'departamento'])
            ->withCount('usuarios')
            ->findOrFail($id);

        $this->open = true;
    }

    public function close(): void
    {
        $this->open  = false;
        $this->cargo = null;
    }

    public function render()
    {
        return view('livewire.configuracion.cargos.cargo-view-modal');
    }
}