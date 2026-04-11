<?php

namespace App\Livewire\Configuracion\Empresas;

use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * EmpresaModal — v2
 *
 * Correcciones:
 *  - unique solo valida contra empresas ACTIVAS.
 *  - Si al crear existe una inactiva con el mismo nombre, la reactiva.
 */
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

    // ── Validación ────────────────────────────────────────────────────────────

    protected function rules(): array
    {
        if ($this->empresaId) {
            $uniqueNombre = Rule::unique('empresas', 'nombre')
                ->ignore($this->empresaId)
                ->where('activo', true);
            $uniqueRif = Rule::unique('empresas', 'rif')
                ->ignore($this->empresaId)
                ->where('activo', true);
        } else {
            $uniqueNombre = Rule::unique('empresas', 'nombre')->where('activo', true);
            $uniqueRif    = Rule::unique('empresas', 'rif')->where('activo', true);
        }

        return [
            'nombre'    => ['required', 'string', 'max:255', $uniqueNombre],
            'rif'       => ['nullable', 'string', 'max:50', $uniqueRif],
            'direccion' => 'nullable|string|max:500',
            'telefono'  => 'nullable|string|max:30',
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la empresa es obligatorio.',
            'nombre.unique'   => 'Ya existe una empresa activa con ese nombre.',
            'nombre.max'      => 'Máximo 255 caracteres.',
            'rif.unique'      => 'Ya existe una empresa activa con ese RIF.',
        ];
    }

    // ── Guardar ───────────────────────────────────────────────────────────────

    public function guardar(): void
    {
        $this->validate();

        try {
            $data = [
                'nombre'    => trim($this->nombre),
                'rif'       => trim($this->rif)       ?: null,
                'direccion' => trim($this->direccion) ?: null,
                'telefono'  => trim($this->telefono)  ?: null,
            ];

            if ($this->empresaId) {
                Empresa::findOrFail($this->empresaId)->update($data);
                $msg = "Empresa «{$this->nombre}» actualizada.";
            } else {
                // Verificar si existe inactiva con el mismo nombre
                $inactiva = Empresa::where('nombre', trim($this->nombre))
                    ->where('activo', false)
                    ->first();

                if ($inactiva) {
                    $inactiva->update(array_merge($data, ['activo' => true]));
                    $msg = "Empresa «{$this->nombre}» reactivada.";
                } else {
                    Empresa::create(array_merge($data, ['activo' => true]));
                    $msg = "Empresa «{$this->nombre}» creada.";
                }
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