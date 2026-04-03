<?php

namespace App\Livewire\Configuracion\Categorias;

use App\Models\CategoriaEquipo;
use Livewire\Attributes\On;
use Livewire\Component;

class CategoriaViewModal extends Component
{
    public bool             $open      = false;
    public ?CategoriaEquipo $categoria = null;
 
    #[On('openCategoriaVer')]
    public function abrir(int $id): void
    {
        $this->categoria = CategoriaEquipo::withCount(['equipos', 'atributos'])->findOrFail($id);
        $this->open      = true;
    }
 
    public function close(): void
    {
        $this->open = false;
        $this->categoria = null;
    }
    public function render()
    {
        return view('livewire.configuracion.categorias.categoria-view-modal');
    }
}
