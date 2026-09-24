<?php

namespace App\Livewire\Almacen;

use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ComponenteModal extends Component
{
    public bool   $open         = false;
    public ?int   $componenteId = null;
    public string $empresa_id   = '';
    public string $nombre       = '';
    public string $descripcion  = '';
    public string $unidad       = 'unidad';

    #[On('openComponenteCrear')]
    public function abrirCrear(): void
    {
        $this->reset(['componenteId', 'nombre', 'descripcion']);
        $this->unidad = 'unidad';
        $actor = Auth::user();
        $this->empresa_id = $actor->hasRole('Analista') ? (string) ($actor->empresa_activa_id ?? '') : '';
        $this->resetValidation();
        $this->authorize('create', ComponenteAlmacen::class);
        $this->open = true;
    }

    #[On('openComponenteEditar')]
    public function abrirEditar(int $id): void
    {
        $c = ComponenteAlmacen::findOrFail($id);
        $this->authorize('update', $c);

        $this->componenteId = $c->id;
        $this->empresa_id   = (string) $c->empresa_id;
        $this->nombre       = $c->nombre;
        $this->descripcion  = $c->descripcion ?? '';
        $this->unidad       = $c->unidad;
        $this->resetValidation();
        $this->open = true;
    }

    #[Computed]
    public function empresasOpciones()
    {
        $actor = Auth::user();

        if ($actor->hasRole('Administrador')) {
            return Empresa::activas()->orderBy('nombre')->pluck('nombre', 'id');
        }

        return Empresa::where('id', $actor->empresa_activa_id)->pluck('nombre', 'id');
    }

    protected function rules(): array
    {
        return [
            'empresa_id'  => 'required|exists:empresas,id',
            'nombre'      => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:500',
            'unidad'      => 'required|string|max:30',
        ];
    }

    protected function messages(): array
    {
        return [
            'empresa_id.required' => 'Debes seleccionar una empresa.',
            'nombre.required'     => 'El nombre es obligatorio.',
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        try {
            $data = [
                'empresa_id'  => $this->empresa_id,
                'nombre'      => trim($this->nombre),
                'descripcion' => trim($this->descripcion) ?: null,
                'unidad'      => trim($this->unidad) ?: 'unidad',
            ];

            if ($this->componenteId) {
                $componente = ComponenteAlmacen::findOrFail($this->componenteId);
                $this->authorize('update', $componente);
                $componente->update($data);
                $msg = "Componente «{$this->nombre}» actualizado.";
            } else {
                $this->authorize('create', ComponenteAlmacen::class);
                ComponenteAlmacen::create(array_merge($data, [
                    'activo'      => true,
                    'creado_por'  => Auth::id(),
                ]));
                $msg = "Componente «{$this->nombre}» creado.";
            }

            $this->close();
            $this->dispatch('componenteGuardado');
            $this->dispatch('toast', type: 'success', message: $msg);
        } catch (\Exception $e) {
            Log::error('ComponenteModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar el componente.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['componenteId', 'empresa_id', 'nombre', 'descripcion', 'unidad']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.almacen.componente-modal');
    }
}
