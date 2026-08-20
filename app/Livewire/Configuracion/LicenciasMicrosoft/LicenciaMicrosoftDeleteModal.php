<?php

namespace App\Livewire\Configuracion\LicenciasMicrosoft;

use App\Models\TipoLicenciaMicrosoft;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class LicenciaMicrosoftDeleteModal extends Component
{
    public bool                   $open         = false;
    public ?TipoLicenciaMicrosoft $tipoLicencia = null;
    public int                    $totalUsuarios = 0;

    #[On('openLicenciaMicrosoftEliminar')]
    public function abrir(int $id): void
    {
        $this->tipoLicencia  = TipoLicenciaMicrosoft::withCount('usuarios')->findOrFail($id);
        $this->totalUsuarios = $this->tipoLicencia->usuarios_count;
        $this->open          = true;
    }

    public function desactivar(): void
    {
        if (! $this->tipoLicencia) return;

        if ($this->totalUsuarios > 0) {
            $this->dispatch('toast', type: 'error',
                message: "No se puede desactivar: {$this->totalUsuarios} usuario(s) tienen asignado este tipo de licencia.");
            $this->close();
            return;
        }

        try {
            $nombre = $this->tipoLicencia->nombre;
            $this->tipoLicencia->update(['activo' => false]);

            $this->close();
            $this->dispatch('licenciaMicrosoftEliminada');
            $this->dispatch('toast', type: 'success', message: "Tipo de licencia «{$nombre}» desactivado.");
        } catch (\Exception $e) {
            Log::error('LicenciaMicrosoftDeleteModal@desactivar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al desactivar el tipo de licencia.');
            $this->close();
        }
    }

    #[On('reactivarLicenciaMicrosoft')]
    public function reactivar(int $id): void
    {
        try {
            $tipoLicencia = TipoLicenciaMicrosoft::findOrFail($id);
            $tipoLicencia->update(['activo' => true]);

            $this->dispatch('licenciaMicrosoftEliminada');
            $this->dispatch('toast', type: 'success', message: "Tipo de licencia «{$tipoLicencia->nombre}» reactivado.");
        } catch (\Exception $e) {
            Log::error('LicenciaMicrosoftDeleteModal@reactivar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al reactivar el tipo de licencia.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['tipoLicencia', 'totalUsuarios']);
    }

    public function render()
    {
        return view('livewire.configuracion.licencias-microsoft.licencia-microsoft-delete-modal');
    }
}
