<?php

namespace App\Livewire\Configuracion\Categorias;

use App\Models\CategoriaEquipo;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * CategoriaModal — v2
 *
 * Cambios respecto a v1:
 *  - El unique en validación ahora solo aplica sobre registros ACTIVOS.
 *    Si existe una categoría inactiva con el mismo nombre, guardar() la reactiva
 *    en lugar de duplicar → resuelve el problema del unique con activo=false.
 */
class CategoriaModal extends Component
{
    public bool   $open        = false;
    public ?int   $categoriaId = null;
    public string $nombre      = '';
    public string $descripcion = '';
    public bool   $asignable   = false;

    // ── Abrir ─────────────────────────────────────────────────────────────────

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

    // ── Validación — unique solo sobre activos ────────────────────────────────

    protected function rules(): array
    {
        if ($this->categoriaId) {
            // Editar: ignorar el propio registro, solo validar contra activos
            $uniqueRule = Rule::unique('categorias_equipos', 'nombre')
                ->ignore($this->categoriaId)
                ->where('activo', true);
        } else {
            // Crear: solo bloquear si existe ACTIVA con mismo nombre
            // Si existe INACTIVA, se maneja por reactivación (no por validación)
            $uniqueRule = Rule::unique('categorias_equipos', 'nombre')
                ->where('activo', true);
        }

        return [
            'nombre'      => ['required', 'string', 'max:100', $uniqueRule],
            'descripcion' => 'nullable|string|max:500',
            'asignable'   => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique'   => 'Ya existe una categoría activa con ese nombre.',
            'nombre.max'      => 'Máximo 100 caracteres.',
        ];
    }

    // ── Guardar ───────────────────────────────────────────────────────────────

    public function guardar(): void
    {
        $this->validate();

        try {
            $data = [
                'nombre'      => trim($this->nombre),
                'descripcion' => trim($this->descripcion) ?: null,
                'asignable'   => $this->asignable,
            ];

            if ($this->categoriaId) {
                // ── Editar ────────────────────────────────────────────────────
                CategoriaEquipo::findOrFail($this->categoriaId)->update($data);
                $msg = "Categoría «{$this->nombre}» actualizada.";

            } else {
                // ── Crear: revisar si existe inactiva con el mismo nombre ─────
                $inactiva = CategoriaEquipo::where('nombre', trim($this->nombre))
                                            ->where('activo', false)
                                            ->first();

                if ($inactiva) {
                    // Reactivar en lugar de crear duplicado
                    $inactiva->update(array_merge($data, ['activo' => true]));
                    $msg = "Categoría «{$this->nombre}» reactivada.";
                } else {
                    CategoriaEquipo::create(array_merge($data, ['activo' => true]));
                    $msg = "Categoría «{$this->nombre}» creada.";
                }
            }

            $this->close();
            $this->dispatch('categoriaGuardada');
            $this->dispatch('toast', type: 'success', message: $msg);

        } catch (\Exception $e) {
            Log::error('CategoriaModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar la categoría.');
        }
    }

    // ── Cerrar ────────────────────────────────────────────────────────────────

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