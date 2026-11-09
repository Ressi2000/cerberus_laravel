<?php

namespace App\Livewire\Configuracion\Depositos;

use App\Models\Deposito;
use App\Models\Empresa;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class DepositoModal extends Component
{
    public bool   $open        = false;
    public ?int   $depositoId  = null;

    public string $empresa_id     = '';
    public string $ubicacion_id   = '';
    public string $nombre         = '';
    public string $descripcion    = '';

    #[On('openDepositoCrear')]
    public function abrirCrear(): void
    {
        $this->authorize('create', Deposito::class);
        $this->reset(['depositoId', 'ubicacion_id', 'nombre', 'descripcion']);

        $actor = Auth::user();
        $this->empresa_id = ! $actor->hasRole('Administrador') ? (string) ($actor->empresa_activa_id ?? '') : '';

        $this->resetValidation();
        $this->open = true;
    }

    #[On('openDepositoEditar')]
    public function abrirEditar(int $id): void
    {
        $deposito = Deposito::findOrFail($id);
        $this->authorize('update', $deposito);

        $this->depositoId   = $deposito->id;
        $this->empresa_id   = (string) $deposito->empresa_id;
        $this->ubicacion_id = (string) ($deposito->ubicacion_id ?? '');
        $this->nombre       = $deposito->nombre;
        $this->descripcion  = $deposito->descripcion ?? '';

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

    /** Ubicaciones de la empresa elegida, para apoyar el depósito en algo que ya existe. */
    #[Computed]
    public function ubicacionesOpciones()
    {
        if (! $this->empresa_id) {
            return collect();
        }

        return Ubicacion::where('activo', true)
            ->where(fn ($q) => $q->where('empresa_id', $this->empresa_id)->orWhere('es_estado', true))
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    protected function rules(): array
    {
        $uniqueRule = Rule::unique('depositos', 'nombre')
            ->where(fn ($q) => $q->where('empresa_id', $this->empresa_id))
            ->ignore($this->depositoId);

        return [
            'empresa_id'   => 'required|exists:empresas,id',
            'ubicacion_id' => 'nullable|exists:ubicaciones,id',
            'nombre'       => ['required', 'string', 'max:150', $uniqueRule],
            'descripcion'  => 'nullable|string|max:500',
        ];
    }

    protected function messages(): array
    {
        return [
            'empresa_id.required' => 'Selecciona una empresa.',
            'nombre.required'     => 'El nombre del depósito es obligatorio.',
            'nombre.unique'       => 'Ya existe un depósito activo con ese nombre en esta empresa.',
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        try {
            $data = [
                'empresa_id'   => $this->empresa_id,
                'ubicacion_id' => $this->ubicacion_id ?: null,
                'nombre'       => trim($this->nombre),
                'descripcion'  => $this->descripcion ?: null,
            ];

            if ($this->depositoId) {
                $deposito = Deposito::findOrFail($this->depositoId);
                $this->authorize('update', $deposito);
                $deposito->update($data);
                $msg = "Depósito «{$this->nombre}» actualizado.";
            } else {
                $this->authorize('create', Deposito::class);
                Deposito::create(array_merge($data, ['activo' => true, 'creado_por' => Auth::id()]));
                $msg = "Depósito «{$this->nombre}» creado.";
            }

            $this->close();
            $this->dispatch('depositoGuardado');
            $this->dispatch('toast', type: 'success', message: $msg);
        } catch (\Exception $e) {
            Log::error('DepositoModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar el depósito.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['depositoId', 'empresa_id', 'ubicacion_id', 'nombre', 'descripcion']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.configuracion.depositos.deposito-modal');
    }
}
