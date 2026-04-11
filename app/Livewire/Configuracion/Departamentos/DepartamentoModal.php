<?php

namespace App\Livewire\Configuracion\Departamentos;

use App\Models\Departamento;
use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class DepartamentoModal extends Component
{
    public bool   $open           = false;
    public ?int   $departamentoId = null;
    public string $nombre         = '';
    public string $descripcion    = '';
    public string $empresa_id     = ''; // '' = global

    #[On('openDepartamentoCrear')]
    public function abrirCrear(): void
    {
        $this->reset(['departamentoId', 'nombre', 'descripcion', 'empresa_id']);
        $this->resetValidation();
        $this->open = true;
    }

    #[On('openDepartamentoEditar')]
    public function abrirEditar(int $id): void
    {
        $d = Departamento::findOrFail($id);

        $this->departamentoId = $d->id;
        $this->nombre         = $d->nombre;
        $this->descripcion    = $d->descripcion ?? '';
        $this->empresa_id     = (string) ($d->empresa_id ?? '');

        $this->resetValidation();
        $this->open = true;
    }

    protected function rules(): array
    {
        $unique = $this->departamentoId
            ? "unique:departamentos,nombre,{$this->departamentoId}"
            : 'unique:departamentos,nombre';

        return [
            'nombre'      => "required|string|max:255|{$unique}",
            'descripcion' => 'nullable|string|max:500',
            'empresa_id'  => 'nullable|exists:empresas,id',
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required'   => 'El nombre es obligatorio.',
            'nombre.unique'     => 'Ya existe un departamento con ese nombre.',
            'nombre.max'        => 'Máximo 255 caracteres.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
        ];
    }

    #[Computed]
    public function empresas()
    {
        return Empresa::orderBy('nombre')->pluck('nombre', 'id');
    }

    public function guardar(): void
    {
        $this->validate();

        try {
            $data = [
                'nombre'      => $this->nombre,
                'descripcion' => $this->descripcion ?: null,
                'empresa_id'  => $this->empresa_id  ?: null,
            ];

            if ($this->departamentoId) {
                Departamento::findOrFail($this->departamentoId)->update($data);
                $msg = "Departamento «{$this->nombre}» actualizado.";
            } else {
                Departamento::create($data);
                $msg = "Departamento «{$this->nombre}» creado.";
            }

            $this->close();
            $this->dispatch('departamentoGuardado');
            $this->dispatch('toast', type: 'success', message: $msg);

        } catch (\Exception $e) {
            Log::error('DepartamentoModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar el departamento.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['departamentoId', 'nombre', 'descripcion', 'empresa_id']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.configuracion.departamentos.departamento-modal');
    }
}