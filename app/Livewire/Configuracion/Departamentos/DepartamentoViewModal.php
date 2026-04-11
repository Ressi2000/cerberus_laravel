<?php

namespace App\Livewire\Configuracion\Departamentos;

use App\Models\Departamento;
use Livewire\Attributes\On;
use Livewire\Component;

class DepartamentoViewModal extends Component
{
    public bool          $open         = false;
    public ?Departamento $departamento = null;

    #[On('openDepartamentoVer')]
    public function abrir(int $id): void
    {
        $this->departamento = Departamento::with('empresa')
            ->withCount(['cargos', 'usuarios'])
            ->findOrFail($id);

        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
        $this->departamento = null;
    }

    public function render()
    {
        return view('livewire.configuracion.departamentos.departamento-view-modal');
    }
}