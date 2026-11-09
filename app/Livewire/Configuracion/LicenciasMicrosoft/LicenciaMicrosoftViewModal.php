<?php

namespace App\Livewire\Configuracion\LicenciasMicrosoft;

use App\Models\TipoLicenciaMicrosoft;
use Livewire\Attributes\On;
use Livewire\Component;

class LicenciaMicrosoftViewModal extends Component
{
    public bool                    $open        = false;
    public ?TipoLicenciaMicrosoft  $tipoLicencia = null;

    #[On('openLicenciaMicrosoftVer')]
    public function abrir(int $id): void
    {
        $this->tipoLicencia = TipoLicenciaMicrosoft::withCount('usuarios')->findOrFail($id);
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
        $this->tipoLicencia = null;
    }

    public function render()
    {
        return view('livewire.configuracion.licencias-microsoft.licencia-microsoft-view-modal');
    }
}
