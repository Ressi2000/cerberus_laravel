<?php

namespace App\Livewire\Configuracion\TareasMantenimiento;

use App\Models\TareaMantenimientoCatalogo;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class TareaMantenimientoModal extends Component
{
    public bool   $open    = false;
    public ?int   $tareaId = null;
    public string $nombre  = '';
    public ?int   $orden   = null;

    #[On('openTareaCrear')]
    public function abrirCrear(): void
    {
        $this->authorize('create', TareaMantenimientoCatalogo::class);
        $this->reset(['tareaId', 'nombre']);
        $this->orden = ((int) TareaMantenimientoCatalogo::max('orden')) + 1;
        $this->resetValidation();
        $this->open = true;
    }

    #[On('openTareaEditar')]
    public function abrirEditar(int $id): void
    {
        $tarea = TareaMantenimientoCatalogo::findOrFail($id);
        $this->authorize('update', $tarea);

        $this->tareaId = $tarea->id;
        $this->nombre  = $tarea->nombre;
        $this->orden   = $tarea->orden;
        $this->resetValidation();
        $this->open = true;
    }

    protected function rules(): array
    {
        $uniqueRule = Rule::unique('tareas_mantenimiento_catalogo', 'nombre')
            ->where('activo', true)
            ->ignore($this->tareaId);

        return [
            'nombre' => ['required', 'string', 'max:150', $uniqueRule],
            'orden'  => 'nullable|integer|min:0|max:1000',
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la tarea es obligatorio.',
            'nombre.unique'   => 'Ya existe una tarea activa con ese nombre.',
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        try {
            $data = [
                'nombre' => trim($this->nombre),
                'orden'  => $this->orden ?? 0,
            ];

            if ($this->tareaId) {
                $tarea = TareaMantenimientoCatalogo::findOrFail($this->tareaId);
                $this->authorize('update', $tarea);
                $tarea->update($data);
                $msg = "Tarea «{$this->nombre}» actualizada.";
            } else {
                $this->authorize('create', TareaMantenimientoCatalogo::class);
                TareaMantenimientoCatalogo::create(array_merge($data, ['activo' => true]));
                $msg = "Tarea «{$this->nombre}» agregada al catálogo.";
            }

            $this->close();
            $this->dispatch('tareaGuardada');
            $this->dispatch('toast', type: 'success', message: $msg);
        } catch (\Exception $e) {
            Log::error('TareaMantenimientoModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar la tarea.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['tareaId', 'nombre', 'orden']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.configuracion.tareas-mantenimiento.tarea-mantenimiento-modal');
    }
}
