<?php

namespace App\Livewire\Asignaciones;

use App\Models\Asignacion;
use App\Models\AsignacionItem;
use App\Models\CategoriaEquipo;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * CrearAsignacion — versión final
 *
 * Paso 1 → Receptor:
 *   - Personal: selector de usuario
 *   - Área común: empresa + departamento + responsable (sin ubicacion_id)
 *
 * Paso 2 → Grilla de equipos disponibles + carrito con vinculación de periféricos
 *   - Cada item del carrito puede marcarse como "periférico de [otro item]"
 *   - Los periféricos se guardan con equipo_padre_id apuntando al item principal
 */
class CrearAsignacion extends Component
{
    use WithPagination;

    // ── Wizard ───────────────────────────────────────────────────────────────
    public int $paso = 1;

    // ── Paso 1: Receptor ─────────────────────────────────────────────────────
    public string $tipo_receptor       = 'usuario';  // 'usuario' | 'area'

    // Receptor personal
    public string $usuario_id          = '';

    // Receptor área común
    public string $area_empresa_id     = '';
    public string $area_departamento_id = '';
    public string $area_responsable_id  = '';

    // Datos comunes
    public string $fecha_asignacion    = '';
    public string $observaciones       = '';

    // ── Paso 2: Grilla ───────────────────────────────────────────────────────
    public string $filtro_categoria    = '';
    public string $filtro_busqueda     = '';

    // ── Carrito ───────────────────────────────────────────────────────────────
    // Cada item: [ 'id', 'codigo', 'categoria', 'serial', 'maquina', 'icono', 'padre_uid' ]
    // 'padre_uid' = '' (sin padre) | uid de otro item del carrito
    public array $carrito = [];

    // ─────────────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->authorize('create', Asignacion::class);
        $this->fecha_asignacion = now()->toDateString();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Computed — Paso 1
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function usuarios()
    {
        $actor = Auth::user();
        $query = User::where('estado', 'Activo')->orderBy('name');

        if ($actor->hasRole('Analista') && $actor->empresa_activa_id) {
            $query->whereHas('empresas', fn($q) =>
                $q->where('empresa_id', $actor->empresa_activa_id)
            );
        }

        return $query->select('id', 'name', 'cargo_id')->with('cargo')->get();
    }

    #[Computed]
    public function empresasArea()
    {
        return Empresa::orderBy('nombre')->pluck('nombre', 'id');
    }

    #[Computed]
    public function departamentosArea()
    {
        if (!$this->area_empresa_id) return collect();
        return Departamento::where('empresa_id', $this->area_empresa_id)
            ->orderBy('nombre')->pluck('nombre', 'id');
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
    // Computed — Paso 2
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function categorias()
    {
        $actor            = Auth::user();
        $estadoDisponible = EstadoEquipo::where('nombre', 'Disponible')->value('id');
        $idsEnCarrito     = collect($this->carrito)->pluck('id')->toArray();

        return CategoriaEquipo::where('asignable', true)
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

        if (strlen($this->filtro_busqueda) >= 2) {
            $s = $this->filtro_busqueda;
            $query->where(function ($q) use ($s) {
                $q->where('codigo_interno', 'like', "%{$s}%")
                  ->orWhere('serial', 'like', "%{$s}%")
                  ->orWhere('nombre_maquina', 'like', "%{$s}%");
            });
        }

        return $query->orderBy('codigo_interno')->paginate(12);
    }

    /**
     * Nombre del receptor para el resumen del carrito.
     */
    #[Computed]
    public function receptorNombre(): string
    {
        if ($this->tipo_receptor === 'usuario' && $this->usuario_id) {
            return $this->usuarios->firstWhere('id', $this->usuario_id)?->name ?? '—';
        }

        if ($this->tipo_receptor === 'area') {
            $partes = array_filter([
                $this->departamentosArea[(int)$this->area_departamento_id] ?? null,
                $this->empresasArea[(int)$this->area_empresa_id] ?? null,
            ]);
            return implode(' — ', $partes) ?: '—';
        }

        return '—';
    }

    /**
     * Solo los equipos del carrito que son PRINCIPALES (sin padre asignado).
     * Sirven como opciones en el selector "Vincular a".
     */
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

    public function updatedFiltroBusqueda(): void
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
        if (collect($this->carrito)->contains('id', $equipoId)) {
            return;
        }

        $estadoDisponible = EstadoEquipo::where('nombre', 'Disponible')->value('id');
        $equipo = Equipo::with('categoria')->find($equipoId);

        if (!$equipo || $equipo->estado_id !== $estadoDisponible || !$equipo->activo) {
            $this->addError('carrito', "El equipo ya no está disponible.");
            return;
        }

        $uid = uniqid('item_');

        $this->carrito[] = [
            'uid'        => $uid,
            'id'         => $equipo->id,
            'codigo'     => $equipo->codigo_interno,
            'categoria'  => $equipo->categoria?->nombre ?? '—',
            'serial'     => $equipo->serial ?? '—',
            'maquina'    => $equipo->nombre_maquina ?? '—',
            'icono'      => $this->iconoCategoria($equipo->categoria?->nombre ?? ''),
            'padre_uid'  => '',  // '' = sin padre (es principal)
        ];

        $this->resetErrorBag('carrito');
        unset($this->equiposDisponibles);
        unset($this->categorias);
    }

    public function quitarDelCarrito(int $index): void
    {
        $uid = $this->carrito[$index]['uid'] ?? null;

        // Si tenía hijos que apuntaban a este uid, limpiar su padre_uid
        if ($uid) {
            $this->carrito = array_map(function ($item) use ($uid) {
                if ($item['padre_uid'] === $uid) {
                    $item['padre_uid'] = '';
                }
                return $item;
            }, $this->carrito);
        }

        array_splice($this->carrito, $index, 1);
        unset($this->equiposDisponibles);
        unset($this->categorias);
    }

    /**
     * Vincula un item del carrito como periférico de otro item.
     * $index     = posición del item hijo en $carrito
     * $padreUid  = uid del item padre ('' = quitar vínculo)
     */
    public function vincularPadre(int $index, string $padreUid): void
    {
        if (!isset($this->carrito[$index])) return;

        // No permitir auto-referencia
        if ($padreUid === $this->carrito[$index]['uid']) return;

        $this->carrito[$index]['padre_uid'] = $padreUid;
    }

    private function iconoCategoria(string $nombre): string
    {
        $n = strtolower($nombre);
        return match(true) {
            str_contains($n, 'laptop')                => 'laptop',
            str_contains($n, 'desktop') || str_contains($n, 'pc') => 'computer',
            str_contains($n, 'monitor') || str_contains($n, 'pantalla') => 'monitor',
            str_contains($n, 'impresora')             => 'print',
            str_contains($n, 'teclado')               => 'keyboard',
            str_contains($n, 'mouse')                 => 'mouse',
            str_contains($n, 'tel')                   => 'phone',
            str_contains($n, 'tablet')                => 'tablet',
            str_contains($n, 'switch') || str_contains($n, 'router') => 'router',
            str_contains($n, 'servidor')              => 'dns',
            str_contains($n, 'ups')                   => 'battery_charging_full',
            default                                   => 'devices_other',
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validaciones
    // ─────────────────────────────────────────────────────────────────────────

    protected function validatePaso1(): void
    {
        if ($this->tipo_receptor === 'usuario') {
            $this->validate([
                'usuario_id'       => 'required|exists:users,id',
                'fecha_asignacion' => 'required|date|before_or_equal:today',
                'observaciones'    => 'nullable|string|max:1000',
            ], [
                'usuario_id.required'              => 'Selecciona un usuario receptor.',
                'fecha_asignacion.before_or_equal' => 'La fecha no puede ser futura.',
            ]);
        } else {
            $this->validate([
                'area_empresa_id'      => 'required|exists:empresas,id',
                'area_departamento_id' => 'required|exists:departamentos,id',
                'area_responsable_id'  => 'required|exists:users,id',
                'fecha_asignacion'     => 'required|date|before_or_equal:today',
                'observaciones'        => 'nullable|string|max:1000',
            ], [
                'area_empresa_id.required'      => 'Selecciona la empresa del área.',
                'area_departamento_id.required' => 'Selecciona el departamento.',
                'area_responsable_id.required'  => 'Selecciona el responsable del área.',
                'fecha_asignacion.before_or_equal' => 'La fecha no puede ser futura.',
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Confirmar
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmar(): void
    {
        $this->authorize('create', Asignacion::class);

        if (empty($this->carrito)) {
            $this->addError('carrito', 'Agrega al menos un equipo al carrito.');
            return;
        }

        try {
            DB::transaction(function () {
                $actor = Auth::user();

                $asignacion = Asignacion::create([
                    'empresa_id'           => $actor->empresa_activa_id ?? $actor->empresa_id,
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

                // Mapa uid → id de AsignacionItem creado (para resolver padre_uid)
                $uidToItemId = [];

                // Primero crear los items PRINCIPALES (sin padre)
                foreach ($this->carrito as $item) {
                    if (!empty($item['padre_uid'])) continue;

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

                // Luego crear los PERIFÉRICOS con su equipo_padre_id resuelto
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

    public function render()
    {
        return view('livewire.asignaciones.crear-asignacion');
    }
}