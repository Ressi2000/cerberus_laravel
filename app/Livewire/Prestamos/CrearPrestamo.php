<?php

namespace App\Livewire\Prestamos;

use App\Models\CategoriaEquipo;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Prestamo;
use App\Models\PrestamoItem;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class CrearPrestamo extends Component
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
    public string $fecha_prestamo       = '';
    public string $fecha_devolucion_esperada = '';
    public string $observaciones        = '';
    public string $empresa_personal_id  = '';

    // ── Paso 2: Filtros ───────────────────────────────────────────────────────
    public string $filtro_categoria = '';
    public string $filtro_busqueda  = '';
    public string $filtro_ubicacion = '';
    public string $vistaEquipos     = 'grilla';

    // ── Carrito ───────────────────────────────────────────────────────────────
    public array $carrito = [];

    // ── Escaneo de código de barras / QR ────────────────────────────────────
    public string $escaneo = '';

    // ─────────────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->fecha_prestamo = now()->format('Y-m-d');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Datos Paso 1
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
                    $u->where('empresa_id', $actor->empresa_activa_id)->orWhere('es_estado', true);
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
            ->where(function ($q) {
                $q->where('empresa_id', $this->area_empresa_id)
                    ->orWhereNull('empresa_id');
            })
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    #[Computed]
    public function responsablesArea()
    {
        return User::where('estado', 'Activo')->orderBy('name')->select('id', 'name')->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Datos Paso 2
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function ubicacionesOpciones()
    {
        $actor = Auth::user();
        $query = Ubicacion::where('activo', true)->orderBy('es_estado')->orderBy('nombre');
        if ($actor->hasRole('Analista') && $actor->empresa_activa_id) {
            $query->where(function ($q) use ($actor) {
                $q->where('empresa_id', $actor->empresa_activa_id)->orWhere('es_estado', true);
            });
        }
        return $query->pluck('nombre', 'id');
    }

    #[Computed]
    public function categorias()
    {
        $actor         = Auth::user();
        $estadoId      = EstadoEquipo::where('nombre', 'Disponible')->value('id');
        $idsEnCarrito  = collect($this->carrito)->pluck('id')->toArray();

        return CategoriaEquipo::where('activo', true)->where('asignable', true)
            ->whereHas('equipos', function ($q) use ($actor, $estadoId, $idsEnCarrito) {
                $q->where('activo', true)->where('estado_id', $estadoId)
                  ->whereNotIn('id', $idsEnCarrito)->visiblePara($actor);
            })
            ->withCount(['equipos as disponibles_count' => function ($q) use ($actor, $estadoId, $idsEnCarrito) {
                $q->where('activo', true)->where('estado_id', $estadoId)
                  ->whereNotIn('id', $idsEnCarrito)->visiblePara($actor);
            }])
            ->orderBy('nombre')
            ->get();
    }

    #[Computed]
    public function equiposDisponibles()
    {
        $actor        = Auth::user();
        $estadoId     = EstadoEquipo::where('nombre', 'Disponible')->value('id');
        $idsEnCarrito = collect($this->carrito)->pluck('id')->toArray();

        $query = Equipo::with(['categoria', 'ubicacion', 'atributosActuales.atributo'])
            ->where('activo', true)
            ->where('estado_id', $estadoId)
            ->whereNotIn('id', $idsEnCarrito)
            ->whereHas('categoria', fn($q) => $q->where('asignable', true))
            ->visiblePara($actor);

        if ($this->filtro_categoria) $query->where('categoria_id', $this->filtro_categoria);
        if ($this->filtro_ubicacion) $query->where('ubicacion_id', $this->filtro_ubicacion);

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
        return collect($this->carrito)->filter(fn($i) => empty($i['padre_uid']))->values()->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Navegación
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

    public function updatedFiltroBusqueda(): void  { $this->resetPage(); }
    public function updatedFiltroUbicacion(): void { $this->resetPage(); }

    public function toggleVistaEquipos(): void
    {
        $this->vistaEquipos = $this->vistaEquipos === 'grilla' ? 'lista' : 'grilla';
    }
    public function updatedAreaEmpresaId(): void
    {
        $this->area_departamento_id = '';
        unset($this->departamentosArea);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Carrito
    // ─────────────────────────────────────────────────────────────────────────

    public function agregarAlCarrito(int $equipoId): void
    {
        if (collect($this->carrito)->contains('id', $equipoId)) return;

        $estadoId = EstadoEquipo::where('nombre', 'Disponible')->value('id');
        $equipo   = Equipo::with('categoria')->find($equipoId);

        if (! $equipo || $equipo->estado_id !== $estadoId || ! $equipo->activo) {
            $this->addError('carrito', 'El equipo ya no está disponible.');
            return;
        }

        $this->carrito[] = [
            'uid'       => uniqid('item_'),
            'id'        => $equipo->id,
            'codigo'    => $equipo->codigo_interno,
            'categoria' => $equipo->categoria?->nombre ?? '—',
            'icono'     => $this->iconoCategoria($equipo->categoria?->nombre ?? ''),
            'padre_uid' => '',
        ];
    }

    public function quitarDelCarrito(string $uid): void
    {
        $this->carrito = collect($this->carrito)
            ->filter(fn($i) => $i['uid'] !== $uid && $i['padre_uid'] !== $uid)
            ->values()
            ->toArray();
    }

    public function setPadre(string $uid, string $padreUid): void
    {
        $this->carrito = collect($this->carrito)->map(function ($item) use ($uid, $padreUid) {
            if ($item['uid'] === $uid) $item['padre_uid'] = $padreUid;
            return $item;
        })->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Escaneo de código de barras / QR
    // - procesarEscaneo(): lectores HID (USB o Bluetooth keyboard-wedge)
    // - procesarEscaneoCamara(): cámara del dispositivo (BarcodeDetector / fallback JS)
    // ─────────────────────────────────────────────────────────────────────────

    public function procesarEscaneo(): void
    {
        $valor         = trim($this->escaneo);
        $this->escaneo = '';
        $this->registrarEscaneo($valor);
    }

    public function procesarEscaneoCamara(string $valor): void
    {
        $this->registrarEscaneo(trim($valor));
    }

    private function registrarEscaneo(string $valor): void
    {
        $this->resetErrorBag('carrito');

        if ($valor === '') {
            return;
        }

        $equipoId = $this->idEquipoDesdeCodigo($valor);

        if (! $equipoId) {
            $mensaje = 'Código no reconocido.';
            $this->addError('carrito', $mensaje);
            $this->dispatch('equipo-escaneado', ok: false, mensaje: $mensaje);
            return;
        }

        if (collect($this->carrito)->contains('id', $equipoId)) {
            $mensaje = 'Ese equipo ya está en el carrito.';
            $this->addError('carrito', $mensaje);
            $this->dispatch('equipo-escaneado', ok: false, mensaje: $mensaje);
            return;
        }

        $totalAntes = count($this->carrito);
        $this->agregarAlCarrito($equipoId);

        if (count($this->carrito) > $totalAntes) {
            $codigo = collect($this->carrito)->last()['codigo'] ?? '';
            $this->dispatch('equipo-escaneado', ok: true, mensaje: "Agregado: {$codigo}");
            return;
        }

        $mensaje = $this->getErrorBag()->first('carrito') ?: 'No se pudo agregar el equipo.';
        $this->dispatch('equipo-escaneado', ok: false, mensaje: $mensaje);
    }

    // El código de barras codifica el id del equipo; el QR codifica la URL de su ficha
    // (.../equipos/{id}) — ambos formatos se resuelven al mismo identificador.
    private function idEquipoDesdeCodigo(string $valor): ?int
    {
        if (ctype_digit($valor)) {
            return (int) $valor;
        }

        if (preg_match('#/equipos/(\d+)#', $valor, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validación
    // ─────────────────────────────────────────────────────────────────────────

    private function validatePaso1(): void
    {
        $rules = ['fecha_prestamo' => 'required|date'];
        $msgs  = [];

        if ($this->tipo_receptor === 'usuario') {
            $rules['usuario_id'] = 'required';
            $msgs['usuario_id.required'] = 'Selecciona un usuario receptor.';

            $actor = Auth::user();
            if ($actor->hasRole('Administrador')) {
                $rules['empresa_personal_id'] = 'required';
                $msgs['empresa_personal_id.required'] = 'Selecciona la empresa para este préstamo.';
            }
        } else {
            $rules['area_empresa_id']      = 'required';
            $rules['area_departamento_id'] = 'required';
            $rules['area_responsable_id']  = 'required';
            $msgs['area_empresa_id.required']      = 'Selecciona la empresa del área.';
            $msgs['area_departamento_id.required'] = 'Selecciona el departamento.';
            $msgs['area_responsable_id.required']  = 'Selecciona un responsable.';
        }

        $this->validate($rules, $msgs);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Confirmar
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmar(): void
    {
        $this->authorize('create', Prestamo::class);

        if (empty($this->carrito)) {
            $this->addError('carrito', 'El carrito está vacío. Agrega al menos un equipo.');
            return;
        }

        $actor = Auth::user();

        try {
            DB::transaction(function () use ($actor) {

                if ($actor->hasRole('Administrador')) {
                    $empresaId = $this->tipo_receptor === 'usuario'
                        ? ($this->empresa_personal_id ?: (User::find($this->usuario_id)?->empresa_id ?? $actor->empresa_id))
                        : $this->area_empresa_id;
                } else {
                    $empresaId = $actor->empresa_activa_id;
                }

                $prestamo = Prestamo::create([
                    'empresa_id'                => $empresaId,
                    'analista_id'               => $actor->id,
                    'usuario_id'                => $this->tipo_receptor === 'usuario' ? $this->usuario_id : null,
                    'area_empresa_id'           => $this->tipo_receptor === 'area' ? $this->area_empresa_id : null,
                    'area_departamento_id'      => $this->tipo_receptor === 'area' ? $this->area_departamento_id : null,
                    'area_responsable_id'       => $this->tipo_receptor === 'area' ? $this->area_responsable_id : null,
                    'fecha_prestamo'            => $this->fecha_prestamo,
                    'fecha_devolucion_esperada' => $this->fecha_devolucion_esperada ?: null,
                    'estado'                    => 'Activo',
                    'observaciones'             => $this->observaciones ?: null,
                ]);

                $estadoEnPrestamo = EstadoEquipo::where('nombre', 'En préstamo')->value('id');
                $uidToItemId      = [];

                // Primero los principales
                foreach ($this->carrito as $item) {
                    if (! empty($item['padre_uid'])) continue;

                    $creado = PrestamoItem::create([
                        'prestamo_id'    => $prestamo->id,
                        'equipo_id'      => $item['id'],
                        'equipo_padre_id' => null,
                        'devuelto'       => false,
                    ]);
                    $uidToItemId[$item['uid']] = $creado->id;

                    if ($estadoEnPrestamo) {
                        Equipo::where('id', $item['id'])->update(['estado_id' => $estadoEnPrestamo]);
                    }
                }

                // Luego los periféricos
                foreach ($this->carrito as $item) {
                    if (empty($item['padre_uid'])) continue;

                    $creado = PrestamoItem::create([
                        'prestamo_id'    => $prestamo->id,
                        'equipo_id'      => $item['id'],
                        'equipo_padre_id' => $uidToItemId[$item['padre_uid']] ?? null,
                        'devuelto'       => false,
                    ]);
                    $uidToItemId[$item['uid']] = $creado->id;

                    if ($estadoEnPrestamo) {
                        Equipo::where('id', $item['id'])->update(['estado_id' => $estadoEnPrestamo]);
                    }
                }
            });

            session()->flash('success', 'Préstamo registrado correctamente.');
            $this->redirect(route('admin.prestamos.index'), navigate: true);

        } catch (\Exception $e) {
            Log::error('CrearPrestamo@confirmar: ' . $e->getMessage());
            $this->addError('general', 'Ocurrió un error al registrar el préstamo.');
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

    public function render()
    {
        return view('livewire.prestamos.crear-prestamo');
    }
}
