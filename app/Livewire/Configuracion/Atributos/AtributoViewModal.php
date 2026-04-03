<?php

namespace App\Livewire\Configuracion\Atributos;

use App\Models\AtributoEquipo;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class AtributoViewModal extends Component
{
    public bool            $open     = false;
    public ?AtributoEquipo $atributo = null;
 
    #[On('openAtributoVer')]
    public function abrir(int $id): void
    {
        $this->atributo = AtributoEquipo::with('categoria')->withCount('valores')->findOrFail($id);
        $this->open     = true;
    }
 
    public function close(): void { $this->open = false; $this->atributo = null; }

    public function render()
    {
        return view('livewire.configuracion.atributos.atributo-view-modal');
    }
}
