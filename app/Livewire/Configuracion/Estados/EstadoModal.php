<?php

namespace App\Livewire\Configuracion\Estados;

use App\Models\EstadoEquipo;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class EstadoModal extends Component
{
    public bool   $open     = false;
    public ?int   $estadoId = null;
    public string $nombre   = '';
 
    #[On('openEstadoCrear')]
    public function abrirCrear(): void
    {
        $this->reset(['estadoId', 'nombre']);
        $this->resetValidation();
        $this->open = true;
    }
 
    #[On('openEstadoEditar')]
    public function abrirEditar(int $id): void
    {
        $e = EstadoEquipo::findOrFail($id);
        $this->estadoId = $e->id;
        $this->nombre   = $e->nombre;
        $this->resetValidation();
        $this->open = true;
    }
 
    protected function rules(): array
    {
        $unique = $this->estadoId
            ? "unique:estados_equipos,nombre,{$this->estadoId}"
            : 'unique:estados_equipos,nombre';
 
        return ['nombre' => "required|string|max:100|{$unique}"];
    }
 
    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique'   => 'Ya existe un estado con ese nombre.',
        ];
    }
 
    public function guardar(): void
    {
        $this->validate();
 
        try {
            if ($this->estadoId) {
                EstadoEquipo::findOrFail($this->estadoId)->update(['nombre' => $this->nombre]);
                $msg = "Estado «{$this->nombre}» actualizado.";
            } else {
                EstadoEquipo::create(['nombre' => $this->nombre]);
                $msg = "Estado «{$this->nombre}» creado.";
            }
 
            $this->close();
            $this->dispatch('estadoGuardado');
            $this->dispatch('toast', type: 'success', message: $msg);
 
        } catch (\Exception $e) {
            Log::error('EstadoModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar el estado.');
        }
    }
 
    public function close(): void
    {
        $this->open = false;
        $this->reset(['estadoId', 'nombre']);
        $this->resetValidation();
    }
 
    public function render()
    {
        return view('livewire.configuracion.estados.estado-modal');
    }
}
