<?php

namespace App\Livewire\Configuracion\LicenciasMicrosoft;

use App\Models\TipoLicenciaMicrosoft;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class LicenciaMicrosoftModal extends Component
{
    public bool   $open              = false;
    public ?int   $tipoLicenciaId    = null;
    public string $nombre            = '';

    #[On('openLicenciaMicrosoftCrear')]
    public function abrirCrear(): void
    {
        $this->reset(['tipoLicenciaId', 'nombre']);
        $this->resetValidation();
        $this->open = true;
    }

    #[On('openLicenciaMicrosoftEditar')]
    public function abrirEditar(int $id): void
    {
        $t = TipoLicenciaMicrosoft::findOrFail($id);
        $this->tipoLicenciaId = $t->id;
        $this->nombre         = $t->nombre;
        $this->resetValidation();
        $this->open = true;
    }

    protected function rules(): array
    {
        $unique = $this->tipoLicenciaId
            ? "unique:tipos_licencia_microsoft,nombre,{$this->tipoLicenciaId}"
            : 'unique:tipos_licencia_microsoft,nombre';

        return ['nombre' => "required|string|max:100|{$unique}"];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique'   => 'Ya existe un tipo de licencia con ese nombre.',
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        try {
            if ($this->tipoLicenciaId) {
                TipoLicenciaMicrosoft::findOrFail($this->tipoLicenciaId)->update(['nombre' => $this->nombre]);
                $msg = "Tipo de licencia «{$this->nombre}» actualizado.";
            } else {
                TipoLicenciaMicrosoft::create(['nombre' => $this->nombre]);
                $msg = "Tipo de licencia «{$this->nombre}» creado.";
            }

            $this->close();
            $this->dispatch('licenciaMicrosoftGuardada');
            $this->dispatch('toast', type: 'success', message: $msg);

        } catch (\Exception $e) {
            Log::error('LicenciaMicrosoftModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar el tipo de licencia.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['tipoLicenciaId', 'nombre']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.configuracion.licencias-microsoft.licencia-microsoft-modal');
    }
}
