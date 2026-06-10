<?php

namespace App\Livewire\Traslados;

use App\Models\CategoriaEquipo;
use App\Models\Equipo;
use App\Models\Traslado;
use App\Models\TrasladoItem;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class CrearTraslado extends Component
{
    use WithPagination;

    // ── Wizard ───────────────────────────────────────────────────────────────
    public int $paso = 1;

    // ── Paso 1: Datos del traslado ────────────────────────────────────────────
    public string $ubicacion_origen_id  = '';
    public string $ubicacion_destino_id = '';
    public string $motivo               = '';
    public string $recibe_id            = '';
    public string $autoriza_id          = '';
    public string $fecha_traslado       = '';
    public string $observaciones        = '';

    // Admin: empresa explícita
    public string $empresa_id = '';

    // ── Paso 2: Filtros de la grilla ─────────────────────────────────────────
    public string $filtro_categoria = '';
    public string $filtro_busqueda  = '';

    // ── Carrito ───────────────────────────────────────────────────────────────
    public array $carrito = [];

    // ─────────────────────────────────────────────────────────────────────────
    // Mount
    // ─────────────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->fecha_traslado = now()->format('Y-m-d');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Datos para Paso 1
    // ─────────────────────────────────────────────────────────────────────────

    /** Ubicaciones de origen: solo las visibles para el analista (donde puede gestionar equipos) */
    #[Computed]
    public function ubicacionesOrigen()
    {
        $actor = Auth::user();

        $query = Ubicacion::where('activo', true)->orderBy('nombre');

        if ($actor->hasRole('Analista') && $actor->empresa_activa_id) {
            $query->where(function ($q) use ($actor) {
                $q->where('empresa_id', $actor->empresa_activa_id)
                    ->orWhere('es_estado', true);
            });
        }

        return $query->get(['id', 'nombre', 'es_estado']);
    }

    /** Ubicaciones de destino: todas las activas (el traslado puede ir a cualquier sede) */
    #[Computed]
    public function ubicacionesDestino()
    {
        return Ubicacion::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'es_estado']);
    }

    #[Computed]
    public function ubicacionesOrigenOpciones(): array
    {
        return $this->ubicacionesOrigen
            ->mapWithKeys(fn($ub) => [
                $ub->id => $ub->nombre . ($ub->es_estado ? ' (foránea)' : '')
            ])
            ->toArray();
    }

    #[Computed]
    public function ubicacionesDestinoOpciones(): array
    {
        return $this->ubicacionesDestino
            ->mapWithKeys(fn($ub) => [
                $ub->id => $ub->nombre . ($ub->es_estado ? ' (foránea)' : '')
            ])
            ->toArray();
    }

    #[Computed]
    public function usuarios()
    {
        $actor = Auth::user();

        return User::where('estado', 'Activo')
            ->when(
                $actor->hasRole('Analista') && $actor->empresa_activa_id,
                fn($q) => $q->whereHas('ubicacion', function ($u) use ($actor) {
                    $u->where('empresa_id', $actor->empresa_activa_id)
                        ->orWhere('es_estado', true);
                })
            )
            ->orderBy('name')
            ->get(['id', 'name', 'cedula']);
    }

    /** Opciones para x-form.select [id => 'Nombre (cédula)'] */
    #[Computed]
    public function usuariosOpciones(): array
    {
        return $this->usuarios
            ->mapWithKeys(fn($u) => [$u->id => $u->name . ($u->cedula ? ' — ' . $u->cedula : '')])
            ->toArray();
    }

    #[Computed]
    public function empresas()
    {
        return \App\Models\Empresa::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
    }

    #[Computed]
    public function empresasOpciones(): array
    {
        return $this->empresas->pluck('nombre', 'id')->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Datos para Paso 2
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function categorias()
    {
        $actor        = Auth::user();
        $idsEnCarrito = collect($this->carrito)->pluck('id')->toArray();
        $origenId     = $this->ubicacion_origen_id ?: null;

        return CategoriaEquipo::where('activo', true)
            ->whereHas('equipos', function ($q) use ($actor, $idsEnCarrito, $origenId) {
                $q->where('activo', true)
                    ->whereNotIn('id', $idsEnCarrito)
                    ->visiblePara($actor)
                    ->when($origenId, fn($sq) => $sq->where('ubicacion_id', $origenId));
            })
            ->withCount(['equipos as disponibles_count' => function ($q) use ($actor, $idsEnCarrito, $origenId) {
                $q->where('activo', true)
                    ->whereNotIn('id', $idsEnCarrito)
                    ->visiblePara($actor)
                    ->when($origenId, fn($sq) => $sq->where('ubicacion_id', $origenId));
            }])
            ->orderBy('nombre')
            ->get();
    }

    #[Computed]
    public function equiposDisponibles()
    {
        $actor        = Auth::user();
        $idsEnCarrito = collect($this->carrito)->pluck('id')->toArray();
        $origenId     = $this->ubicacion_origen_id ?: null;

        $query = Equipo::with(['categoria', 'ubicacion', 'estado', 'atributosActuales.atributo'])
            ->where('activo', true)
            ->whereNotIn('id', $idsEnCarrito)
            ->visiblePara($actor)
            ->when($origenId, fn($q) => $q->where('ubicacion_id', $origenId));

        if ($this->filtro_categoria) {
            $query->where('categoria_id', $this->filtro_categoria);
        }

        if (strlen($this->filtro_busqueda) >= 2) {
            $s = $this->filtro_busqueda;
            $query->where(function ($q) use ($s) {
                $q->where('codigo_interno', 'like', "%{$s}%")
                    ->orWhereHas('atributosActuales', function ($av) use ($s) {
                        $av->where('valor', 'like', "%{$s}%")
                            ->whereHas('atributo', fn($a) => $a->where('visible_en_tabla', true));
                    });
            });
        }

        return $query->orderBy('codigo_interno')->paginate(12);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Navegación del wizard
    // ─────────────────────────────────────────────────────────────────────────

    public function irPaso2(): void
    {
        $this->validarPaso1();
        $this->paso = 2;
        $this->resetPage();
    }

    public function volverPaso1(): void
    {
        $this->paso = 1;
    }

    public function setCategoriaFiltro(string $categoriaId): void
    {
        $this->filtro_categoria = ($this->filtro_categoria === $categoriaId) ? '' : $categoriaId;
        $this->resetPage();
    }

    public function updatedFiltroBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatedUbicacionOrigenId(): void
    {
        $this->carrito = [];
        $this->filtro_categoria = '';
        unset($this->equiposDisponibles, $this->categorias);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Gestión del carrito
    // ─────────────────────────────────────────────────────────────────────────

    public function agregarAlCarrito(int $equipoId): void
    {
        if (collect($this->carrito)->contains('id', $equipoId)) return;

        $equipo = Equipo::with('categoria')->find($equipoId);

        if (! $equipo || ! $equipo->activo) {
            $this->addError('carrito', 'El equipo ya no está disponible.');
            return;
        }

        $this->carrito[] = [
            'id'        => $equipo->id,
            'codigo'    => $equipo->codigo_interno,
            'categoria' => $equipo->categoria?->nombre ?? '—',
            'ubicacion' => $equipo->ubicacion?->nombre ?? '—',
            'icono'     => $this->iconoCategoria($equipo->categoria?->nombre ?? ''),
        ];

        unset($this->equiposDisponibles, $this->categorias);
    }

    public function quitarDelCarrito(int $equipoId): void
    {
        $this->carrito = collect($this->carrito)
            ->filter(fn($i) => $i['id'] !== $equipoId)
            ->values()
            ->toArray();

        unset($this->equiposDisponibles, $this->categorias);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validación
    // ─────────────────────────────────────────────────────────────────────────

    private function validarPaso1(): void
    {
        $actor = Auth::user();

        $rules = [
            'ubicacion_origen_id'  => 'required|different:ubicacion_destino_id',
            'ubicacion_destino_id' => 'required',
            'motivo'               => 'required|min:10',
            'recibe_id'            => 'required',
            'autoriza_id'          => 'required',
            'fecha_traslado'       => 'required|date',
        ];

        if ($actor->hasRole('Administrador')) {
            $rules['empresa_id'] = 'required';
        }

        $this->validate($rules, [
            'ubicacion_origen_id.required'  => 'Selecciona la ubicación de origen.',
            'ubicacion_origen_id.different' => 'El origen y destino no pueden ser iguales.',
            'ubicacion_destino_id.required' => 'Selecciona la ubicación de destino.',
            'motivo.required'               => 'El motivo es obligatorio.',
            'motivo.min'                    => 'El motivo debe tener al menos 10 caracteres.',
            'recibe_id.required'            => 'Selecciona quien recibe.',
            'autoriza_id.required'          => 'Selecciona quien autoriza.',
            'fecha_traslado.required'       => 'La fecha del traslado es obligatoria.',
            'empresa_id.required'           => 'Selecciona la empresa para este traslado.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Confirmar traslado
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmar(): void
    {
        $this->authorize('create', Traslado::class);

        if (empty($this->carrito)) {
            $this->addError('carrito', 'Agrega al menos un equipo al traslado.');
            return;
        }

        $actor = Auth::user();

        try {
            DB::transaction(function () use ($actor) {
                $empresaId = $actor->hasRole('Administrador')
                    ? (int) $this->empresa_id
                    : $actor->empresa_activa_id;

                $numero = Traslado::generarNumero($empresaId);

                $traslado = Traslado::create([
                    'empresa_id'           => $empresaId,
                    'numero'               => $numero,
                    'ubicacion_origen_id'  => $this->ubicacion_origen_id,
                    'ubicacion_destino_id' => $this->ubicacion_destino_id,
                    'motivo'               => $this->motivo,
                    'recibe_id'            => $this->recibe_id,
                    'autoriza_id'          => $this->autoriza_id,
                    'realizado_por_id'     => $actor->id,
                    'fecha_traslado'       => $this->fecha_traslado,
                    'observaciones'        => $this->observaciones ?: null,
                ]);

                foreach ($this->carrito as $item) {
                    TrasladoItem::create([
                        'traslado_id' => $traslado->id,
                        'equipo_id'   => $item['id'],
                    ]);

                    // Actualizar la ubicación del equipo al destino
                    Equipo::where('id', $item['id'])
                        ->update(['ubicacion_id' => $this->ubicacion_destino_id]);
                }
            });

            session()->flash('success', 'Traslado registrado correctamente.');
            $this->redirect(route('admin.traslados.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('CrearTraslado@confirmar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al registrar el traslado.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function iconoCategoria(string $nombre): string
    {
        return match (strtolower($nombre)) {
            'laptop', 'portátil', 'notebook'  => 'laptop',
            'desktop', 'pc', 'computadora'    => 'desktop_windows',
            'monitor', 'pantalla'             => 'monitor',
            'impresora', 'printer'            => 'print',
            'teléfono', 'telefono', 'celular' => 'smartphone',
            'switch', 'router', 'red'         => 'router',
            'servidor', 'server'              => 'dns',
            default                           => 'devices',
        };
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.traslados.crear-traslado');
    }
}
