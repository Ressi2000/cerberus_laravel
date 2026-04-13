<?php

namespace App\Livewire\Asignaciones;

use App\Models\Asignacion;
use App\Models\AsignacionItem;
use App\Models\CategoriaEquipo;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * CrearAsignacion v2
 *
 * Mejoras respecto a v1:
 *
 * B1 — Filtro de búsqueda ampliado:
 *   Antes solo buscaba en codigo_interno, serial y nombre_maquina.
 *   Ahora también busca en atributos EAV (marca, modelo) y ubicación del equipo.
 *   Se añade filtro de ubicación como select independiente.
 *
 * B2 — Toggle grilla / lista en el carrito de equipos disponibles:
 *   Nueva propiedad $vistaEquipos: 'grilla' | 'lista'
 *   El toggle es Alpine puro (sin round-trip al servidor):
 *   el estado se guarda en la propiedad Livewire para persistir entre renders.
 */
class CrearAsignacion extends Component
{
    use WithPagination;

    // ── Wizard ───────────────────────────────────────────────────────────────
    public int $paso = 1;

    // ── Paso 1: Receptor ─────────────────────────────────────────────────────
    public string $tipo_receptor        = 'usuario';
    public string $usuario_id           = '';
    public string $area_empresa_id      = '';
    public string $area_departamento_id = '';
    public string $area_responsable_id  = '';
    public string $fecha_asignacion     = '';
    public string $observaciones        = '';
    public string $empresa_personal_id  = '';  // solo visible para Admin en asignación personal

    // ── Paso 2: Filtros de la grilla ─────────────────────────────────────────
    public string $filtro_categoria  = '';
    public string $filtro_busqueda   = '';
    public string $filtro_ubicacion  = '';   // B1: nuevo filtro

    // ── Vista de la grilla: 'grilla' | 'lista' ────────────────────────────────
    public string $vistaEquipos = 'grilla'; // B2: nuevo toggle

    // ── Carrito ───────────────────────────────────────────────────────────────
    public array $carrito = [];

    // ─────────────────────────────────────────────────────────────────────────
    // Mount
    // ─────────────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->fecha_asignacion = now()->format('Y-m-d');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Datos para Paso 1
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function usuarios()
    {
        $actor = Auth::user();

        return User::with(['cargo'])
            ->where('estado', 'Activo')
            ->when(
                $actor->hasRole('Analista') && $actor->empresa_activa_id,
                fn($q) => $q->whereHas('ubicacion', function ($u) use ($actor) {
                    $u->where('empresa_id', $actor->empresa_activa_id)
                        ->orWhere('es_estado', true);
                })
            )
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function empresasArea()
    {
        return Empresa::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id');
    }

    #[Computed]
    public function departamentosArea()
    {
        if (! $this->area_empresa_id) return collect();

        return Departamento::where('activo', true)
            ->where('empresa_id', $this->area_empresa_id)
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    #[Computed]
    public function responsablesArea()
    {
        return User::where('estado', 'Activo')
            ->orderBy('name')
            ->select('id', 'name')
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Datos para Paso 2 — B1: nueva opción de ubicación
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function ubicacionesOpciones()
    {
        $actor  = Auth::user();

        $query = Ubicacion::where('activo', true)->orderBy('nombre');

        if ($actor->hasRole('Analista') && $actor->empresa_activa_id) {
            $query->where(function ($q) use ($actor) {
                $q->where('empresa_id', $actor->empresa_activa_id)
                    ->orWhere('es_estado', true);
            });
        }

        return $query->pluck('nombre', 'id');
    }

    #[Computed]
    public function categorias()
    {
        $actor            = Auth::user();
        $estadoDisponible = EstadoEquipo::where('nombre', 'Disponible')->value('id');
        $idsEnCarrito     = collect($this->carrito)->pluck('id')->toArray();

        return CategoriaEquipo::where('activo', true)->where('asignable', true)
            ->whereHas('equipos', function ($q) use ($actor, $estadoDisponible, $idsEnCarrito) {
                $q->where('activo', true)
                    ->where('estado_id', $estadoDisponible)
                    ->whereNotIn('id', $idsEnCarrito)
                    ->visiblePara($actor);
            })
            ->withCount(['equipos as disponibles_count' => function ($q) use ($actor, $estadoDisponible, $idsEnCarrito) {
                $q->where('activo', true)
                    ->where('estado_id', $estadoDisponible)
                    ->whereNotIn('id', $idsEnCarrito)
                    ->visiblePara($actor);
            }])
            ->orderBy('nombre')
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Equipos disponibles — B1: búsqueda ampliada
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function equiposDisponibles()
    {
        $actor            = Auth::user();
        $estadoDisponible = EstadoEquipo::where('nombre', 'Disponible')->value('id');
        $idsEnCarrito     = collect($this->carrito)->pluck('id')->toArray();

        $query = Equipo::with(['categoria', 'ubicacion', 'atributosActuales.atributo'])
            ->where('activo', true)
            ->where('estado_id', $estadoDisponible)
            ->whereNotIn('id', $idsEnCarrito)
            ->whereHas('categoria', fn($q) => $q->where('asignable', true))
            ->visiblePara($actor);

        if ($this->filtro_categoria) {
            $query->where('categoria_id', $this->filtro_categoria);
        }

        // B1 — Filtro de ubicación
        if ($this->filtro_ubicacion) {
            $query->where('ubicacion_id', $this->filtro_ubicacion);
        }

        // B1 — Búsqueda ampliada:
        //   código interno, serial, hostname, + atributos EAV (marca, modelo)
        if (strlen($this->filtro_busqueda) >= 2) {
            $s = $this->filtro_busqueda;
            $query->where(function ($q) use ($s) {
                $q->where('codigo_interno', 'like', "%{$s}%")
                    ->orWhere('serial', 'like', "%{$s}%")
                    ->orWhere('nombre_maquina', 'like', "%{$s}%")
                    // Búsqueda en atributos EAV (marca, modelo y cualquier otro)
                    ->orWhereHas('atributosActuales', function ($av) use ($s) {
                        $av->where('valor', 'like', "%{$s}%")
                            ->whereHas('atributo', fn($a) => $a->where('visible_en_tabla', true));
                    });
            });
        }

        return $query->orderBy('codigo_interno')->paginate(12);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Receptor
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function receptorNombre(): string
    {
        if ($this->tipo_receptor === 'usuario' && $this->usuario_id) {
            return $this->usuarios->firstWhere('id', $this->usuario_id)?->name ?? '—';
        }

        if ($this->tipo_receptor === 'area') {
            $partes = array_filter([
                $this->departamentosArea[(int) $this->area_departamento_id] ?? null,
                $this->empresasArea[(int) $this->area_empresa_id] ?? null,
            ]);
            return implode(' — ', $partes) ?: '—';
        }

        return '—';
    }

    #[Computed]
    public function itemsPrincipalesCarrito(): array
    {
        return collect($this->carrito)
            ->filter(fn($i) => empty($i['padre_uid']))
            ->values()
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Navegación del wizard
    // ─────────────────────────────────────────────────────────────────────────

    public function irPaso2(): void
    {
        $this->validatePaso1();
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

    // B2 — Método para alternar la vista (puede llamarse desde Livewire o dejarse solo en Alpine)
    public function toggleVistaEquipos(): void
    {
        $this->vistaEquipos = $this->vistaEquipos === 'grilla' ? 'lista' : 'grilla';
    }

    public function updatedFiltroBusqueda(): void
    {
        $this->resetPage();
    }
    public function updatedFiltroUbicacion(): void
    {
        $this->resetPage();
    }
    public function updatedAreaEmpresaId(): void
    {
        $this->area_departamento_id = '';
        unset($this->departamentosArea);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Gestión del carrito
    // ─────────────────────────────────────────────────────────────────────────

    public function agregarAlCarrito(int $equipoId): void
    {
        if (collect($this->carrito)->contains('id', $equipoId)) return;

        $estadoDisponible = EstadoEquipo::where('nombre', 'Disponible')->value('id');
        $equipo           = Equipo::with('categoria')->find($equipoId);

        if (! $equipo || $equipo->estado_id !== $estadoDisponible || ! $equipo->activo) {
            $this->addError('carrito', 'El equipo ya no está disponible.');
            return;
        }

        $uid = uniqid('item_');

        $this->carrito[] = [
            'uid'      => $uid,
            'id'       => $equipo->id,
            'codigo'   => $equipo->codigo_interno,
            'categoria' => $equipo->categoria?->nombre ?? '—',
            'serial'   => $equipo->serial ?? '—',
            'maquina'  => $equipo->nombre_maquina ?? '—',
            'icono'    => $this->iconoCategoria($equipo->categoria?->nombre ?? ''),
            'padre_uid' => '',
        ];
    }

    public function quitarDelCarrito(string $uid): void
    {
        // Quitar el item y sus hijos
        $this->carrito = collect($this->carrito)
            ->filter(fn($i) => $i['uid'] !== $uid && $i['padre_uid'] !== $uid)
            ->values()
            ->toArray();
    }

    public function setPadre(string $uid, string $padreUid): void
    {
        $this->carrito = collect($this->carrito)->map(function ($item) use ($uid, $padreUid) {
            if ($item['uid'] === $uid) {
                $item['padre_uid'] = $padreUid;
            }
            return $item;
        })->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validación Paso 1
    // ─────────────────────────────────────────────────────────────────────────

    private function validatePaso1(): void
    {
        if ($this->tipo_receptor === 'usuario') {
            $this->validate(
                ['usuario_id' => 'required', 'fecha_asignacion' => 'required|date'],
                ['usuario_id.required' => 'Selecciona un usuario receptor.']
            );
            $actor = Auth::user();
            if ($actor->hasRole('Administrador') && $this->tipo_receptor === 'usuario') {
                $this->validate(
                    ['empresa_personal_id' => 'required'],
                    ['empresa_personal_id.required' => 'Selecciona la empresa para esta asignación.']
                );
            }
        } else {
            $this->validate([
                'area_empresa_id'      => 'required',
                'area_departamento_id' => 'required',
                'area_responsable_id'  => 'required',
                'fecha_asignacion'     => 'required|date',
            ], [
                'area_empresa_id.required'      => 'Selecciona la empresa del área.',
                'area_departamento_id.required' => 'Selecciona el departamento.',
                'area_responsable_id.required'  => 'Selecciona un responsable.',
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Confirmar asignación
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmar(): void
    {
        $this->authorize('create', Asignacion::class);

        if (empty($this->carrito)) {
            $this->addError('carrito', 'El carrito está vacío. Agrega al menos un equipo.');
            return;
        }

        $actor = Auth::user();

        try {
            DB::transaction(function () use ($actor) {

                if ($actor->hasRole('Administrador')) {
                    if ($this->tipo_receptor === 'usuario') {
                        // Si el admin seleccionó empresa explícitamente, úsala; si no, cae a la del usuario
                        $empresaId = $this->empresa_personal_id
                            ?: (User::find($this->usuario_id)?->empresa_id ?? $actor->empresa_id);
                    } else {
                        $empresaId = $this->area_empresa_id;
                    }
                } else {
                    $empresaId = $actor->empresa_activa_id;
                }

                $asignacion = Asignacion::create([
                    'empresa_id'           => $empresaId,
                    'usuario_id'           => $this->tipo_receptor === 'usuario' ? $this->usuario_id : null,
                    'area_empresa_id'      => $this->tipo_receptor === 'area' ? $this->area_empresa_id : null,
                    'area_departamento_id' => $this->tipo_receptor === 'area' ? $this->area_departamento_id : null,
                    'area_responsable_id'  => $this->tipo_receptor === 'area' ? $this->area_responsable_id : null,
                    'analista_id'          => $actor->id,
                    'fecha_asignacion'     => $this->fecha_asignacion,
                    'estado'               => 'Activa',
                    'observaciones'        => $this->observaciones ?: null,
                ]);

                $estadoAsignado = EstadoEquipo::where('nombre', 'Asignado')->value('id');
                $uidToItemId    = [];

                foreach ($this->carrito as $item) {
                    if (! empty($item['padre_uid'])) continue;

                    $creado = AsignacionItem::create([
                        'asignacion_id'  => $asignacion->id,
                        'equipo_id'      => $item['id'],
                        'equipo_padre_id' => null,
                        'devuelto'       => false,
                    ]);

                    $uidToItemId[$item['uid']] = $creado->id;

                    if ($estadoAsignado) {
                        Equipo::where('id', $item['id'])->update(['estado_id' => $estadoAsignado]);
                    }
                }

                foreach ($this->carrito as $item) {
                    if (empty($item['padre_uid'])) continue;

                    $padreItemId = $uidToItemId[$item['padre_uid']] ?? null;

                    $creado = AsignacionItem::create([
                        'asignacion_id'   => $asignacion->id,
                        'equipo_id'       => $item['id'],
                        'equipo_padre_id' => $padreItemId,
                        'devuelto'        => false,
                    ]);

                    $uidToItemId[$item['uid']] = $creado->id;

                    if ($estadoAsignado) {
                        Equipo::where('id', $item['id'])->update(['estado_id' => $estadoAsignado]);
                    }
                }
            });

            session()->flash('success', 'Asignación registrada correctamente.');
            $this->redirect(route('admin.asignaciones.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('CrearAsignacion@confirmar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al registrar la asignación.');
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
        return view('livewire.asignaciones.crear-asignacion');
    }
}
