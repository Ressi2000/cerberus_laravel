<?php

namespace App\Livewire\Configuracion\Categorias;

use App\Models\CategoriaEquipo;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class CategoriaModal extends Component
{
    public bool   $open        = false;
    public ?int   $categoriaId = null;
    public string $nombre      = '';
    public string $descripcion = '';
    public bool   $asignable   = false;

    #[On('openCategoriaCrear')]
    public function abrirCrear(): void
    {
        $this->reset(['categoriaId', 'nombre', 'descripcion', 'asignable']);
        $this->resetValidation();
        $this->open = true;
    }

    #[On('openCategoriaEditar')]
    public function abrirEditar(int $id): void
    {
        $c = CategoriaEquipo::findOrFail($id);
        $this->categoriaId = $c->id;
        $this->nombre      = $c->nombre;
        $this->descripcion = $c->descripcion ?? '';
        $this->asignable   = (bool) $c->asignable;
        $this->resetValidation();
        $this->open = true;
    }

    protected function rules(): array
    {
        $unique = $this->categoriaId
            ? "unique:categorias_equipos,nombre,{$this->categoriaId}"
            : 'unique:categorias_equipos,nombre';

        return [
            'nombre'      => "required|string|max:100|{$unique}",
            'descripcion' => 'nullable|string|max:500',
            'asignable'   => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique'   => 'Ya existe una categoría con ese nombre.',
            'nombre.max'      => 'Máximo 100 caracteres.',
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        try {
            $data = [
                'nombre'      => $this->nombre,
                'descripcion' => $this->descripcion ?: null,
                'asignable'   => $this->asignable,
            ];

            if ($this->categoriaId) {
                CategoriaEquipo::findOrFail($this->categoriaId)->update($data);
                $msg = "Categoría «{$this->nombre}» actualizada.";
            } else {
                CategoriaEquipo::create($data);
                $msg = "Categoría «{$this->nombre}» creada.";
            }

            $this->close();
            $this->dispatch('categoriaGuardada');
            $this->dispatch('toast', type: 'success', message: $msg);
        } catch (\Exception $e) {
            Log::error('CategoriaModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar la categoría.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['categoriaId', 'nombre', 'descripcion', 'asignable']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.configuracion.categorias.categoria-modal');
    }
}
