<?php

namespace App\Livewire\Configuracion\Cargos;

use App\Models\Cargo;
use App\Models\Departamento;
use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CargoModal extends Component
{
    public bool   $open           = false;
    public ?int   $cargoId        = null;
    public string $nombre         = '';
    public string $empresa_id     = ''; // '' = global
    public string $departamento_id = '';

    #[On('openCargoCrear')]
    public function abrirCrear(): void
    {
        $this->reset(['cargoId', 'nombre', 'empresa_id', 'departamento_id']);
        $this->resetValidation();
        $this->open = true;
    }

    #[On('openCargoEditar')]
    public function abrirEditar(int $id): void
    {
        $c = Cargo::findOrFail($id);

        $this->cargoId         = $c->id;
        $this->nombre          = $c->nombre;
        $this->empresa_id      = (string) ($c->empresa_id      ?? '');
        $this->departamento_id = (string) ($c->departamento_id ?? '');

        $this->resetValidation();
        $this->open = true;
    }

    /** Al cambiar empresa, limpiar departamento para evitar combinaciones inválidas */
    public function updatedEmpresaId(): void
    {
        $this->departamento_id = '';
    }

    protected function rules(): array
    {
        $unique = $this->cargoId
            ? "unique:cargos,nombre,{$this->cargoId}"
            : 'unique:cargos,nombre';

        return [
            'nombre'          => "required|string|max:255|{$unique}",
            'empresa_id'      => 'nullable|exists:empresas,id',
            'departamento_id' => 'required|exists:departamentos,id',
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required'          => 'El nombre es obligatorio.',
            'nombre.unique'            => 'Ya existe un cargo con ese nombre.',
            'nombre.max'               => 'Máximo 255 caracteres.',
            'empresa_id.exists'        => 'La empresa seleccionada no es válida.',
            'departamento_id.required' => 'Debe seleccionar un departamento.',
            'departamento_id.exists'   => 'El departamento seleccionado no es válido.',
        ];
    }

    #[Computed]
    public function empresas()
    {
        return Empresa::orderBy('nombre')->pluck('nombre', 'id');
    }

    /**
     * Departamentos disponibles en el modal.
     * Muestra globales (empresa_id = null) + los de la empresa seleccionada.
     * Si no hay empresa seleccionada, solo muestra los globales.
     */
    #[Computed]
    public function departamentos()
    {
        return Departamento::where(function ($q) {
                $q->whereNull('empresa_id');
                if ($this->empresa_id) {
                    $q->orWhere('empresa_id', $this->empresa_id);
                }
            })
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    public function guardar(): void
    {
        $this->validate();

        try {
            $data = [
                'nombre'          => $this->nombre,
                'empresa_id'      => $this->empresa_id      ?: null,
                'departamento_id' => $this->departamento_id,
            ];

            if ($this->cargoId) {
                Cargo::findOrFail($this->cargoId)->update($data);
                $msg = "Cargo «{$this->nombre}» actualizado.";
            } else {
                Cargo::create($data);
                $msg = "Cargo «{$this->nombre}» creado.";
            }

            $this->close();
            $this->dispatch('cargoGuardado');
            $this->dispatch('toast', type: 'success', message: $msg);

        } catch (\Exception $e) {
            Log::error('CargoModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar el cargo.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['cargoId', 'nombre', 'empresa_id', 'departamento_id']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.configuracion.cargos.cargo-modal');
    }
}