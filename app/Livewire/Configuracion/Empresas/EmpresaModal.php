<?php

namespace App\Livewire\Configuracion\Empresas;

use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class EmpresaModal extends Component
{
    public bool   $open      = false;
    public ?int   $empresaId = null;
    public string $nombre    = '';
    public string $rif       = '';
    public string $direccion = '';
    public string $telefono  = '';

    #[On('openEmpresaCrear')]
    public function abrirCrear(): void
    {
        $this->reset(['empresaId', 'nombre', 'rif', 'direccion', 'telefono']);
        $this->resetValidation();
        $this->open = true;
    }

    #[On('openEmpresaEditar')]
    public function abrirEditar(int $id): void
    {
        $e = Empresa::findOrFail($id);

        $this->empresaId = $e->id;
        $this->nombre    = $e->nombre;
        $this->rif       = $e->rif       ?? '';
        $this->direccion = $e->direccion ?? '';
        $this->telefono  = $e->telefono  ?? '';

        $this->resetValidation();
        $this->open = true;
    }

    protected function rules(): array
    {
        $unique = $this->empresaId
            ? "unique:empresas,nombre,{$this->empresaId}"
            : 'unique:empresas,nombre';

        return [
            'nombre'    => "required|string|max:255|{$unique}",
            'rif'       => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:500',
            'telefono'  => 'nullable|string|max:30',
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la empresa es obligatorio.',
            'nombre.unique'   => 'Ya existe una empresa con ese nombre.',
            'nombre.max'      => 'Máximo 255 caracteres.',
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        try {
            $data = [
                'nombre'    => $this->nombre,
                'rif'       => $this->rif       ?: null,
                'direccion' => $this->direccion ?: null,
                'telefono'  => $this->telefono  ?: null,
            ];

            if ($this->empresaId) {
                Empresa::findOrFail($this->empresaId)->update($data);
                $msg = "Empresa «{$this->nombre}» actualizada.";
            } else {
                Empresa::create($data);
                $msg = "Empresa «{$this->nombre}» creada.";
            }

            $this->close();
            $this->dispatch('empresaGuardada');
            $this->dispatch('toast', type: 'success', message: $msg);

        } catch (\Exception $e) {
            Log::error('EmpresaModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar la empresa.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['empresaId', 'nombre', 'rif', 'direccion', 'telefono']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.configuracion.empresas.empresa-modal');
    }
}