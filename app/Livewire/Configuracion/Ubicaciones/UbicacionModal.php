<?php

namespace App\Livewire\Configuracion\Ubicaciones;

use App\Models\Empresa;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class UbicacionModal extends Component
{
    public bool   $open        = false;
    public ?int   $ubicacionId = null;
    public string $nombre      = '';
    public string $descripcion = '';
    public string $empresa_id  = '';
    public bool   $es_estado   = false;

    #[On('openUbicacionCrear')]
    public function abrirCrear(): void
    {
        $this->reset(['ubicacionId', 'nombre', 'descripcion', 'empresa_id', 'es_estado']);
        $this->resetValidation();
        $this->open = true;
    }

    #[On('openUbicacionEditar')]
    public function abrirEditar(int $id): void
    {
        $u = Ubicacion::findOrFail($id);

        $this->ubicacionId = $u->id;
        $this->nombre      = $u->nombre;
        $this->descripcion = $u->descripcion ?? '';
        $this->empresa_id  = (string) ($u->empresa_id ?? '');
        $this->es_estado   = (bool) $u->es_estado;

        $this->resetValidation();
        $this->open = true;
    }

    protected function rules(): array
    {
        $unique = $this->ubicacionId
            ? "unique:ubicaciones,nombre,{$this->ubicacionId}"
            : 'unique:ubicaciones,nombre';

        return [
            'nombre'      => "required|string|max:255|{$unique}",
            'descripcion' => 'nullable|string|max:500',
            'empresa_id'  => 'required|exists:empresas,id',
            'es_estado'   => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required'    => 'El nombre es obligatorio.',
            'nombre.unique'      => 'Ya existe una ubicación con ese nombre.',
            'nombre.max'         => 'Máximo 255 caracteres.',
            'empresa_id.required'=> 'Debe seleccionar una empresa.',
            'empresa_id.exists'  => 'La empresa seleccionada no es válida.',
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
                'empresa_id'  => $this->empresa_id,
                'es_estado'   => $this->es_estado,
            ];

            if ($this->ubicacionId) {
                Ubicacion::findOrFail($this->ubicacionId)->update($data);
                $msg = "Ubicación «{$this->nombre}» actualizada.";
            } else {
                Ubicacion::create($data);
                $msg = "Ubicación «{$this->nombre}» creada.";
            }

            $this->close();
            $this->dispatch('ubicacionGuardada');
            $this->dispatch('toast', type: 'success', message: $msg);

        } catch (\Exception $e) {
            Log::error('UbicacionModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar la ubicación.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['ubicacionId', 'nombre', 'descripcion', 'empresa_id', 'es_estado']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.configuracion.ubicaciones.ubicacion-modal');
    }
}