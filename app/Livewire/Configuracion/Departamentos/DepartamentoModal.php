<?php

namespace App\Livewire\Configuracion\Departamentos;

use App\Models\Departamento;
use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * DepartamentoModal — v2
 *
 * Correcciones:
 *  - unique solo valida contra departamentos ACTIVOS.
 *  - Si al crear existe uno inactivo con el mismo nombre, lo reactiva.
 *  - Select de empresa solo muestra empresas ACTIVAS.
 */
class DepartamentoModal extends Component
{
    public bool   $open           = false;
    public ?int   $departamentoId = null;
    public string $nombre         = '';
    public string $descripcion    = '';
    public string $empresa_id     = '';

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

    // ── Validación ────────────────────────────────────────────────────────────

    protected function rules(): array
    {
        if ($this->departamentoId) {
            $uniqueRule = Rule::unique('departamentos', 'nombre')
                ->ignore($this->departamentoId)
                ->where('activo', true);
        } else {
            $uniqueRule = Rule::unique('departamentos', 'nombre')
                ->where('activo', true);
        }

        return [
            'nombre'      => ['required', 'string', 'max:255', $uniqueRule],
            'descripcion' => 'nullable|string|max:500',
            'empresa_id'  => 'nullable|exists:empresas,id',
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required'   => 'El nombre es obligatorio.',
            'nombre.unique'     => 'Ya existe un departamento activo con ese nombre.',
            'nombre.max'        => 'Máximo 255 caracteres.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
        ];
    }

    // ── Computed ──────────────────────────────────────────────────────────────

    /** Solo empresas ACTIVAS */
    #[Computed]
    public function empresas()
    {
        return Empresa::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id');
    }

    // ── Guardar ───────────────────────────────────────────────────────────────

    public function guardar(): void
    {
        $this->validate();

        try {
            $data = [
                'nombre'      => trim($this->nombre),
                'descripcion' => trim($this->descripcion) ?: null,
                'empresa_id'  => $this->empresa_id ?: null,
            ];

            if ($this->departamentoId) {
                Departamento::findOrFail($this->departamentoId)->update($data);
                $msg = "Departamento «{$this->nombre}» actualizado.";
            } else {
                // Verificar si existe inactivo con el mismo nombre (y misma empresa)
                $inactivo = Departamento::where('nombre', trim($this->nombre))
                    ->where('empresa_id', $this->empresa_id ?: null)
                    ->where('activo', false)
                    ->first();

                if ($inactivo) {
                    $inactivo->update(array_merge($data, ['activo' => true]));
                    $msg = "Departamento «{$this->nombre}» reactivado.";
                } else {
                    Departamento::create(array_merge($data, ['activo' => true]));
                    $msg = "Departamento «{$this->nombre}» creado.";
                }
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