<?php

namespace App\Livewire\Configuracion\Empresas;

use App\Models\Empresa;
use Livewire\Attributes\On;
use Livewire\Component;

class EmpresaViewModal extends Component
{
    public bool     $open    = false;
    public ?Empresa $empresa = null;

    #[On('openEmpresaVer')]
    public function abrir(int $id): void
    {
        $this->empresa = Empresa::withCount(['usuarios', 'equipos', 'ubicaciones', 'departamentos'])
            ->findOrFail($id);

        $this->open = true;
    }

    public function close(): void
    {
        $this->open    = false;
        $this->empresa = null;
    }

    public function render()
    {
        return view('livewire.configuracion.empresas.empresa-view-modal');
    }
}