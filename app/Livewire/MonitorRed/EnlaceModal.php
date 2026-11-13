<?php

namespace App\Livewire\MonitorRed;

use App\Models\Empresa;
use App\Models\MonitorEnlace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class EnlaceModal extends Component
{
    public bool   $open          = false;
    public ?int   $enlaceId      = null;
    public string $empresa_id    = '';
    public string $nombre        = '';
    public string $proveedor     = '';
    public string $tipo_conexion = 'Fibra';
    public string $gateway       = '';
    public string $ip_publica    = '';
    public string $asn           = '';
    public string $bajada        = '';
    public string $subida        = '';
    public bool   $principal     = false;
    public bool   $con_cuota     = false;
    public bool   $activo        = true;
    public string $observaciones = '';

    #[On('openEnlaceCrear')]
    public function abrirCrear(): void
    {
        $this->authorize('create', MonitorEnlace::class);
        $this->limpiar();

        $actor = Auth::user();
        $this->empresa_id = $actor->hasRole('Administrador') ? '' : (string) ($actor->empresa_activa_id ?? '');
        if ($this->empresa_id === '' && $this->empresasOpciones->count() === 1) {
            $this->empresa_id = (string) $this->empresasOpciones->keys()->first();
        }

        $this->open = true;
    }

    #[On('openEnlaceEditar')]
    public function abrirEditar(int $id): void
    {
        $e = MonitorEnlace::findOrFail($id);
        $this->authorize('update', $e);
        $this->limpiar();

        $this->enlaceId      = $e->id;
        $this->empresa_id    = (string) $e->empresa_id;
        $this->nombre        = $e->nombre;
        $this->proveedor     = $e->proveedor;
        $this->tipo_conexion = $e->tipo_conexion;
        $this->gateway       = $e->gateway ?? '';
        $this->ip_publica    = $e->ip_publica ?? '';
        $this->asn           = $e->asn ?? '';
        $this->bajada        = $e->bajada_contratada_mbps !== null ? (string) $e->bajada_contratada_mbps : '';
        $this->subida        = $e->subida_contratada_mbps !== null ? (string) $e->subida_contratada_mbps : '';
        $this->principal     = $e->principal;
        $this->con_cuota     = $e->con_cuota;
        $this->activo        = $e->activo;
        $this->observaciones = $e->observaciones ?? '';
        $this->open          = true;
    }

    #[Computed]
    public function empresasOpciones()
    {
        $actor = Auth::user();

        return $actor->hasRole('Administrador')
            ? Empresa::activas()->orderBy('nombre')->pluck('nombre', 'id')
            : Empresa::where('id', $actor->empresa_activa_id)->pluck('nombre', 'id');
    }

    protected function rules(): array
    {
        return [
            'empresa_id'    => ['required', Rule::exists('empresas', 'id')],
            'nombre'        => ['required', 'string', 'max:100'],
            'proveedor'     => ['required', 'string', 'max:80'],
            'tipo_conexion' => ['required', Rule::in(MonitorEnlace::TIPOS)],
            'gateway'       => ['nullable', 'ip'],
            'ip_publica'    => ['nullable', 'ip', function ($attr, $v, $fail) {
                if ($v && ! filter_var($v, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    $fail('Debe ser una IP pública (no 192.168.x.x, 10.x.x.x, etc.). Si el enlace no tiene IP pública fija, déjala vacía.');
                }
            }],
            'asn'           => ['nullable', 'regex:/^(AS)?\d{1,10}$/i'],
            'bajada'        => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'subida'        => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'empresa_id.required' => 'Debes seleccionar una empresa.',
            'nombre.required'     => 'Ponle un nombre al enlace (ej. "Fibra principal").',
            'proveedor.required'  => 'Indica el proveedor de internet.',
            'gateway.ip'          => 'La puerta de enlace debe ser una IP (ej. 192.168.1.1).',
            'ip_publica.ip'       => 'Debe ser una IP válida.',
            'asn.regex'           => 'El ASN es un número, ej. 8048 o AS8048.',
        ];
    }

    public function guardar(): void
    {
        foreach (['gateway', 'ip_publica', 'asn', 'nombre', 'proveedor'] as $campo) {
            $this->{$campo} = trim($this->{$campo});
        }
        $this->validate();

        $actor = Auth::user();
        if (! $actor->hasRole('Administrador') && (int) $this->empresa_id !== (int) $actor->empresa_activa_id) {
            $this->addError('empresa_id', 'Solo puedes registrar enlaces de tu empresa activa.');
            return;
        }

        // Única por empresa, no global: un enlace centralizado (p. ej. una sola IP
        // pública en la sede principal que da internet a varias localidades)
        // se registra en cada localidad con la misma IP.
        if ($this->ip_publica !== '' && MonitorEnlace::where('empresa_id', $this->empresa_id)
                ->where('ip_publica', $this->ip_publica)
                ->when($this->enlaceId, fn ($q) => $q->where('id', '!=', $this->enlaceId))->exists()) {
            $this->addError('ip_publica', 'Esa IP pública ya está registrada en otro enlace de esta localidad.');
            return;
        }

        $data = [
            'empresa_id'             => (int) $this->empresa_id,
            'nombre'                 => $this->nombre,
            'proveedor'              => $this->proveedor,
            'tipo_conexion'          => $this->tipo_conexion,
            'gateway'                => $this->gateway ?: null,
            'ip_publica'             => $this->ip_publica ?: null,
            'asn'                    => MonitorEnlace::normalizarAsn($this->asn),
            'bajada_contratada_mbps' => $this->bajada === '' ? null : (float) $this->bajada,
            'subida_contratada_mbps' => $this->subida === '' ? null : (float) $this->subida,
            'principal'              => $this->principal,
            'con_cuota'              => $this->con_cuota,
            'activo'                 => $this->activo,
            'observaciones'          => trim($this->observaciones) ?: null,
        ];

        try {
            if ($this->enlaceId) {
                $enlace = MonitorEnlace::findOrFail($this->enlaceId);
                $this->authorize('update', $enlace);
                $enlace->update($data);
            } else {
                $this->authorize('create', MonitorEnlace::class);
                MonitorEnlace::create($data + ['creado_por' => $actor->id]);
            }

            $this->dispatch('toast', type: 'success', message: "Enlace «{$this->nombre}» guardado.");
            $this->close();
            $this->dispatch('enlaceGuardado');
        } catch (\Exception $e) {
            Log::error('EnlaceModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar el enlace.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->limpiar();
    }

    private function limpiar(): void
    {
        $this->reset(['enlaceId', 'empresa_id', 'nombre', 'proveedor', 'tipo_conexion', 'gateway', 'ip_publica', 'asn',
                      'bajada', 'subida', 'principal', 'con_cuota', 'activo', 'observaciones']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.monitor-red.enlace-modal');
    }
}
