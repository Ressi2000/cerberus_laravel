<?php

namespace App\Livewire\Configuracion\Ubicaciones;

use App\Models\Empresa;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * UbicacionModal — v2
 *
 * Corrección: unique solo valida contra ubicaciones ACTIVAS.
 * Si al crear existe una inactiva con el mismo nombre y empresa, la reactiva.
 * Selects de empresa filtran solo empresas ACTIVAS.
 */
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

    // ── Validación ────────────────────────────────────────────────────────────

    protected function rules(): array
    {
        if ($this->ubicacionId) {
            $uniqueRule = Rule::unique('ubicaciones', 'nombre')
                ->ignore($this->ubicacionId)
                ->where('activo', true);
        } else {
            $uniqueRule = Rule::unique('ubicaciones', 'nombre')
                ->where('activo', true);
        }

        return [
            'nombre'      => ['required', 'string', 'max:255', $uniqueRule],
            'descripcion' => 'nullable|string|max:500',
            // Requerido solo si NO es ubicación foránea
            'empresa_id'  => $this->es_estado
                ? 'nullable'
                : 'required|exists:empresas,id',
            'es_estado'   => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required'     => 'El nombre es obligatorio.',
            'nombre.unique'       => 'Ya existe una ubicación activa con ese nombre.',
            'nombre.max'          => 'Máximo 255 caracteres.',
            'empresa_id.required' => 'Debe seleccionar una empresa.',
            'empresa_id.exists'   => 'La empresa seleccionada no es válida.',
        ];
    }

    // ── Computed ──────────────────────────────────────────────────────────────

    /** Solo empresas ACTIVAS en el select */
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
                'empresa_id'  => $this->es_estado ? null : ($this->empresa_id ?: null),
                'es_estado'   => $this->es_estado,
            ];

            if ($this->ubicacionId) {
                Ubicacion::findOrFail($this->ubicacionId)->update($data);
                $msg = "Ubicación «{$this->nombre}» actualizada.";
            } else {
                // ← AQUÍ va el fragmento: al crear, verificar si existe una inactiva
                $inactivaQuery = Ubicacion::where('nombre', trim($this->nombre))
                    ->where('activo', false);

                if ($this->es_estado) {
                    $inactivaQuery->whereNull('empresa_id');
                } else {
                    $inactivaQuery->where('empresa_id', $this->empresa_id);
                }

                $inactiva = $inactivaQuery->first();

                if ($inactiva) {
                    $inactiva->update(array_merge($data, ['activo' => true]));
                    $msg = "Ubicación «{$this->nombre}» reactivada.";
                } else {
                    Ubicacion::create(array_merge($data, ['activo' => true]));
                    $msg = "Ubicación «{$this->nombre}» creada.";
                }
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
