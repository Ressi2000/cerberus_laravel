<?php

namespace App\Livewire\MonitorRed;

use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\MonitorHost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class MonitorHostModal extends Component
{
    public bool   $open        = false;
    public ?int   $hostId      = null;
    public string $empresa_id  = '';
    public string $nombre      = '';
    public string $host        = '';
    public string $tipo        = 'Servicio';
    public string $proveedor   = '';
    public string $puerto_tcp  = '';
    public string $observaciones = '';

    // Vínculo opcional con el inventario
    public ?int   $equipo_id     = null;
    public string $equipoBusqueda = '';

    #[On('openMonitorHostCrear')]
    public function abrirCrear(): void
    {
        $this->authorize('create', MonitorHost::class);
        $this->limpiar();

        $actor = Auth::user();
        $this->empresa_id = $actor->hasRole('Administrador') ? '' : (string) ($actor->empresa_activa_id ?? '');

        if ($this->empresa_id === '' && $this->empresasOpciones->count() === 1) {
            $this->empresa_id = (string) $this->empresasOpciones->keys()->first();
        }

        $this->open = true;
    }

    #[On('openMonitorHostEditar')]
    public function abrirEditar(int $id): void
    {
        $h = MonitorHost::with('equipo:id,codigo_interno')->findOrFail($id);
        $this->authorize('update', $h);
        $this->limpiar();

        $this->hostId        = $h->id;
        $this->empresa_id    = (string) $h->empresa_id;
        $this->nombre        = $h->nombre;
        $this->host          = $h->host;
        $this->tipo          = $h->tipo;
        $this->proveedor     = $h->proveedor ?? '';
        $this->puerto_tcp    = (string) ($h->puerto_tcp ?? '');
        $this->observaciones = $h->observaciones ?? '';
        $this->equipo_id     = $h->equipo_id;
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

    #[Computed]
    public function tiposSugeridos(): array
    {
        return collect(config('monitor_red.tipos_sugeridos', []))
            ->merge(MonitorHost::visiblePara(Auth::user())->distinct()->pluck('tipo'))
            ->unique()->sort()->values()->all();
    }

    /** Equipos del inventario que coinciden con lo escrito (código interno). */
    #[Computed]
    public function equiposEncontrados()
    {
        if (mb_strlen(trim($this->equipoBusqueda)) < 2) {
            return collect();
        }

        return Equipo::visiblePara(Auth::user())
            ->with('categoria:id,nombre')
            ->where('codigo_interno', 'like', '%' . trim($this->equipoBusqueda) . '%')
            ->orderBy('codigo_interno')
            ->limit(8)
            ->get(['id', 'codigo_interno', 'categoria_id']);
    }

    #[Computed]
    public function equipoSeleccionado(): ?Equipo
    {
        return $this->equipo_id ? Equipo::with('categoria:id,nombre')->find($this->equipo_id) : null;
    }

    public function elegirEquipo(int $id): void
    {
        $equipo = Equipo::visiblePara(Auth::user())->findOrFail($id);
        $this->equipo_id = $equipo->id;
        $this->equipoBusqueda = '';

        if (trim($this->nombre) === '') {
            $this->nombre = $equipo->codigo_interno;
        }
    }

    public function quitarEquipo(): void
    {
        $this->equipo_id = null;
    }

    protected function rules(): array
    {
        return [
            'empresa_id'    => ['required', Rule::exists('empresas', 'id')],
            'nombre'        => ['nullable', 'string', 'max:100'],
            'host'          => ['required', 'string', 'max:255', function ($attr, $valor, $fail) {
                if (! MonitorHost::hostValido($valor)) {
                    $fail('Escribe una IP (ej. 192.168.1.1) o un nombre DNS válido, sin http:// ni rutas.');
                }
            }],
            'tipo'          => ['required', 'string', 'max:40'],
            'proveedor'     => ['nullable', 'string', 'max:60'],
            'puerto_tcp'    => ['nullable', 'integer', 'between:1,65535'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'equipo_id'     => ['nullable', 'integer', Rule::exists('equipos', 'id')],
        ];
    }

    protected function messages(): array
    {
        return [
            'empresa_id.required' => 'Debes seleccionar una empresa.',
            'host.required'       => 'La IP o el nombre DNS es obligatorio.',
            'puerto_tcp.between'  => 'El puerto debe estar entre 1 y 65535.',
            'puerto_tcp.integer'  => 'El puerto debe ser un número.',
        ];
    }

    public function guardar(): void
    {
        $this->host = trim($this->host);
        $this->validate();

        // Un Analista solo puede registrar hosts en su empresa activa.
        $actor = Auth::user();
        if (! $actor->hasRole('Administrador') && (int) $this->empresa_id !== (int) $actor->empresa_activa_id) {
            $this->addError('empresa_id', 'Solo puedes registrar hosts en tu empresa activa.');
            return;
        }

        $duplicado = MonitorHost::where('empresa_id', $this->empresa_id)
            ->where('host', $this->host)
            ->where('puerto_tcp', $this->puerto_tcp === '' ? null : (int) $this->puerto_tcp)
            ->when($this->hostId, fn ($q) => $q->where('id', '!=', $this->hostId))
            ->exists();

        if ($duplicado) {
            $this->addError('host', 'Este host ya está siendo monitoreado en esta empresa.');
            return;
        }

        $data = [
            'empresa_id'    => (int) $this->empresa_id,
            'equipo_id'     => $this->equipo_id,
            'nombre'        => trim($this->nombre) ?: $this->host,
            'host'          => $this->host,
            'tipo'          => trim($this->tipo) ?: 'Servicio',
            'proveedor'     => trim($this->proveedor) ?: null,
            'puerto_tcp'    => $this->puerto_tcp === '' ? null : (int) $this->puerto_tcp,
            'observaciones' => trim($this->observaciones) ?: null,
        ];

        try {
            if ($this->hostId) {
                $h = MonitorHost::findOrFail($this->hostId);
                $this->authorize('update', $h);
                $h->update($data);
                $msg = "Host «{$data['nombre']}» actualizado.";
            } else {
                $this->authorize('create', MonitorHost::class);
                MonitorHost::create($data + ['creado_por' => $actor->id]);
                $msg = "Host «{$data['nombre']}» agregado. Se revisará en el próximo minuto.";
            }

            $this->close();
            $this->dispatch('monitorHostGuardado');
            $this->dispatch('toast', type: 'success', message: $msg);
        } catch (\Exception $e) {
            Log::error('MonitorHostModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar el host.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->limpiar();
    }

    private function limpiar(): void
    {
        $this->reset(['hostId', 'empresa_id', 'nombre', 'host', 'tipo', 'proveedor', 'puerto_tcp', 'observaciones', 'equipo_id', 'equipoBusqueda']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.monitor-red.monitor-host-modal');
    }
}
