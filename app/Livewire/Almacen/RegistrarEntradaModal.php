<?php

namespace App\Livewire\Almacen;

use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\FamiliaComponente;
use App\Models\MantenimientoComponente;
use App\Models\PiezaExtraida;
use App\Services\ExtraccionPiezaService;
use App\Services\PiezaManualService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Registrar entrada — la ÚNICA forma de meter stock al almacén.
 *
 *   1. Familia (RAM, Bisagras…).
 *   2. Variante: si la familia tiene ficha técnica (RAM), se llenan sus
 *      valores y la variante sale sola ("RAM (Capacidad (GB): 8 / Tipo: DDR4)").
 *      Si no, se elige una existente o se crea una nueva por nombre.
 *   3. Cantidad. Por unidad: nueva o usada (de qué equipo salió) y seriales,
 *      uno por línea. A granel: solo la cantidad.
 *
 * Reemplaza a "Nuevo componente" + "Movimiento de stock → Entrada" + "Pieza
 * nueva de atributo". Las salidas (ajuste, dañada) siguen en MovimientoStockModal.
 */
class RegistrarEntradaModal extends Component
{
    public bool $open = false;

    public string $empresa_id   = '';
    public string $familiaId    = '';
    /** Variante existente (id) o "nueva" para crear una por nombre (familias sin ficha). */
    public string $componenteId = '';
    public string $nuevoNombre  = '';
    public string $nuevaUnidad  = 'unidad';

    /** Valores de la ficha técnica (familias con ficha): campo_id => valor. */
    public array $valores = [];

    public ?int   $cantidad       = 1;
    public string $condicion      = PiezaExtraida::CONDICION_NUEVO;
    public string $seriales       = '';
    public string $motivo         = '';
    public string $observaciones  = '';

    /**
     * Usada: de qué equipo(s) salió. Puede venir de varios equipos en la
     * misma entrada; cada pieza queda ligada a SU equipo de origen.
     *   equipo:     id del equipo
     *   instancias: familias con ficha — cuáles entradas de su ficha salieron
     *               (así solo se declara lo que el equipo tiene, y su ficha se actualiza)
     *   cantidad:   familias sin ficha — cuántas salieron de ese equipo
     *   seriales:   uno por línea, en el mismo orden
     *
     * @var array<int, array{equipo: string, instancias: array, cantidad: int|string|null, seriales: string}>
     */
    public array $origenes = [];

    /** Piezas que acaban de entrar (para imprimir sus etiquetas). */
    public array $recienRegistradas = [];
    public string $resumen = '';

    #[On('openRegistrarEntrada')]
    public function abrir(?int $componenteId = null, ?int $familiaId = null): void
    {
        $this->authorize('create', ComponenteAlmacen::class);

        $this->reset(['familiaId', 'componenteId', 'nuevoNombre', 'valores', 'seriales', 'motivo', 'observaciones', 'recienRegistradas', 'resumen']);
        $this->origenes = [self::origenVacio()];
        $this->nuevaUnidad = 'unidad';
        $this->cantidad    = 1;
        $this->condicion   = PiezaExtraida::CONDICION_NUEVO;

        $actor = Auth::user();
        $this->empresa_id = $actor->hasRole('Administrador') ? '' : (string) ($actor->empresa_activa_id ?? '');

        // Desde una variante concreta (fila del almacén o solicitud pendiente).
        if ($componenteId && ($c = ComponenteAlmacen::find($componenteId))) {
            $this->authorize('registrarMovimiento', $c);
            $this->empresa_id   = (string) $c->empresa_id;
            $this->familiaId    = (string) ($c->familia_id ?? '');
            $this->componenteId = (string) $c->id;
            $this->valores      = $this->valoresDeVariante($c);
        } elseif ($familiaId) {
            $this->familiaId = (string) $familiaId;
        }

        $this->resetValidation();
        $this->open = true;
    }

    // ── Datos para el formulario ─────────────────────────────────────────────

    #[Computed]
    public function empresasOpciones()
    {
        $actor = Auth::user();

        return $actor->hasRole('Administrador')
            ? Empresa::activas()->orderBy('nombre')->pluck('nombre', 'id')
            : Empresa::where('id', $actor->empresa_activa_id)->pluck('nombre', 'id');
    }

    #[Computed]
    public function familias()
    {
        return FamiliaComponente::activas()->orderBy('nombre')->get();
    }

    #[Computed]
    public function familia(): ?FamiliaComponente
    {
        return $this->familiaId ? FamiliaComponente::find($this->familiaId) : null;
    }

    /** La familia tiene ficha y alguna categoría la usa: la variante sale de los valores. */
    #[Computed]
    public function usaFicha(): bool
    {
        return (bool) ($this->familia?->tieneFicha() && $this->familia->atributoRepresentativo());
    }

    #[Computed]
    public function variantes()
    {
        if (! $this->familia || ! $this->empresa_id) {
            return collect();
        }

        return ComponenteAlmacen::where('familia_id', $this->familia->id)
            ->where('empresa_id', $this->empresa_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    /** Variante a la que va la entrada, para mostrarla antes de guardar. */
    #[Computed]
    public function nombreVariante(): ?string
    {
        if ($this->usaFicha) {
            $valores = array_filter($this->valores, fn ($v) => $v !== '' && $v !== null);

            return $valores ? $this->familia->describirValor($valores) : null;
        }

        if ($this->componenteId === 'nueva') {
            return trim($this->nuevoNombre) ?: null;
        }

        return $this->variantes->firstWhere('id', (int) $this->componenteId)?->nombre;
    }

    #[Computed]
    public function equiposOpciones()
    {
        return $this->empresa_id
            ? Equipo::where('empresa_id', $this->empresa_id)->orderBy('codigo_interno')->pluck('codigo_interno', 'id')
            : collect();
    }

    /** Usada + familia con ficha: la pieza se toma de la ficha del equipo de origen. */
    #[Computed]
    public function desdeFichaDelEquipo(): bool
    {
        return $this->usaFicha && $this->condicion === PiezaExtraida::CONDICION_REUTILIZADO;
    }

    private static function origenVacio(): array
    {
        return ['equipo' => '', 'instancias' => [], 'cantidad' => 1, 'seriales' => ''];
    }

    public function agregarOrigen(): void
    {
        $this->origenes[] = self::origenVacio();
    }

    public function quitarOrigen(int $i): void
    {
        unset($this->origenes[$i]);
        $this->origenes = array_values($this->origenes) ?: [self::origenVacio()];
        $this->resetValidation();
    }

    /** Equipos elegidos como origen (de la empresa), por id. */
    #[Computed]
    public function equiposOrigen()
    {
        $ids = collect($this->origenes)->pluck('equipo')->filter()->map(fn ($id) => (int) $id)->unique();

        return $ids->isEmpty() || ! $this->empresa_id
            ? collect()
            : Equipo::with('categoria')->where('empresa_id', $this->empresa_id)->whereIn('id', $ids)->get()->keyBy('id');
    }

    /** La característica de la categoría del equipo que usa esta familia (ej. la RAM de Laptop). */
    public function atributoEn(?Equipo $equipo): ?\App\Models\AtributoEquipo
    {
        return $equipo && $this->familia
            ? \App\Models\AtributoEquipo::where('categoria_id', $equipo->categoria_id)->where('familia_id', $this->familia->id)->first()
            : null;
    }

    /**
     * Para cada origen: el equipo, su característica de esta familia y lo que
     * su ficha dice que tiene (familias con ficha).
     */
    #[Computed]
    public function detalleOrigenes(): array
    {
        return collect($this->origenes)->map(function ($o) {
            $equipo   = $o['equipo'] ? ($this->equiposOrigen[(int) $o['equipo']] ?? null) : null;
            $atributo = $this->usaFicha ? $this->atributoEn($equipo) : null;
            $ficha    = $atributo
                ? \App\Models\EquipoAtributoGrupoInstancia::where('equipo_id', $equipo->id)
                    ->where('atributo_id', $atributo->id)
                    ->where('es_actual', true)
                    ->orderBy('orden')
                    ->get()
                    ->map(fn ($i) => (object) ['id' => $i->id, 'descripcion' => $atributo->describirValor($i->valores ?? [])])
                : collect();

            return (object) ['equipo' => $equipo, 'atributo' => $atributo, 'ficha' => $ficha];
        })->all();
    }

    /** "2 de EQ-00057 · 1 de EQ-00031" — para revisar antes de guardar. */
    #[Computed]
    public function resumenOrigenes(): ?string
    {
        $partes = collect($this->origenes)->map(function ($o) {
            $equipo = $o['equipo'] ? ($this->equiposOrigen[(int) $o['equipo']] ?? null) : null;
            $n = $this->usaFicha ? count($o['instancias'] ?? []) : (int) ($o['cantidad'] ?? 0);

            return $equipo && $n > 0 ? "{$n} de {$equipo->codigo_interno}" : null;
        })->filter();

        return $partes->isEmpty() ? null : $partes->implode(' · ');
    }

    /**
     * Se abrió desde una variante pero la ficha escrita lleva a OTRA (ej.
     * una variante creada con nombre libre, "RAM PRUEBA"): se avisa antes
     * de guardar para no duplicar sin querer.
     */
    #[Computed]
    public function avisoVariante(): ?string
    {
        if (! $this->usaFicha || ! ctype_digit($this->componenteId) || ! $this->nombreVariante) {
            return null;
        }

        $elegida = ComponenteAlmacen::find((int) $this->componenteId);

        return $elegida && $elegida->nombre !== $this->nombreVariante ? $elegida->nombre : null;
    }

    /** Si cambia el equipo de un origen, lo marcado de la ficha anterior ya no aplica. */
    public function updatedOrigenes($valor, ?string $clave = null): void
    {
        if ($clave && preg_match('/^(\d+)\.equipo$/', $clave, $m)) {
            $this->origenes[(int) $m[1]]['instancias'] = [];
        }
        unset($this->equiposOrigen, $this->detalleOrigenes, $this->resumenOrigenes);
    }

    public function updatedCondicion(): void
    {
        $this->origenes = [self::origenVacio()];
    }

    public function updatedEmpresaId(): void
    {
        $this->componenteId = '';
        $this->origenes = [self::origenVacio()];
    }

    public function updatedFamiliaId(): void
    {
        $this->componenteId = '';
        $this->valores = [];
        $this->resetValidation();
    }

    // ── Guardar ──────────────────────────────────────────────────────────────

    protected function rules(): array
    {
        $rules = [
            'empresa_id'    => 'required|exists:empresas,id',
            'familiaId'     => 'required|exists:familias_componentes,id',
            'cantidad'      => 'required|integer|min:1|max:500',
            'motivo'        => 'nullable|string|max:150',
            'observaciones' => 'nullable|string|max:500',
        ];

        if ($this->desdeFichaDelEquipo) {
            // Los valores salen de la ficha de cada equipo: solo hay que elegir cuáles.
            $rules['origenes']                = 'required|array|min:1|max:20';
            $rules['origenes.*.equipo']       = 'required';
            $rules['origenes.*.instancias']   = 'required|array|min:1';
            $rules['origenes.*.instancias.*'] = 'integer';
            $rules['origenes.*.seriales']     = 'nullable|string|max:5000';
            unset($rules['cantidad']);
        } elseif ($this->usaFicha) {
            // La ficha define la variante: completa (si no, quedaría "RAM" a secas).
            foreach ($this->familia->campos as $campo) {
                $rules["valores.{$campo['id']}"] = 'required|string|max:255';
            }
        } elseif ($this->componenteId === 'nueva') {
            $rules['nuevoNombre'] = 'required|string|max:150';
            $rules['nuevaUnidad'] = 'required|string|max:30';
        } else {
            $rules['componenteId'] = ['required', Rule::in($this->variantes->pluck('id')->map(fn ($id) => (string) $id)->all())];
        }

        if (! $this->familia?->esAGranel()) {
            $rules['condicion'] = 'required|in:' . PiezaExtraida::CONDICION_NUEVO . ',' . PiezaExtraida::CONDICION_REUTILIZADO;
            $rules['seriales']  = 'nullable|string|max:5000';
            if ($this->condicion === PiezaExtraida::CONDICION_REUTILIZADO && ! $this->desdeFichaDelEquipo) {
                // Sin ficha: cuántas salieron de cada equipo.
                $rules['origenes']              = 'required|array|min:1|max:20';
                $rules['origenes.*.equipo']     = 'required';
                $rules['origenes.*.cantidad']   = 'required|integer|min:1|max:500';
                $rules['origenes.*.seriales']   = 'nullable|string|max:5000';
                unset($rules['cantidad'], $rules['seriales']);
            }
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'familiaId.required'      => 'Elige la familia.',
            'componenteId.required'   => 'Elige la variante, o crea una nueva.',
            'componenteId.in'         => 'Elige la variante, o crea una nueva.',
            'nuevoNombre.required'    => 'Ponle nombre a la nueva variante (ej. Bisagra HP 14").',
            'valores.*.required'      => 'Completa este campo de la ficha.',
            'origenes.*.equipo.required'     => 'Indica de qué equipo salió.',
            'origenes.*.instancias.required' => 'Marca cuál (o cuáles) de la ficha de este equipo salieron.',
            'origenes.*.instancias.min'      => 'Marca cuál (o cuáles) de la ficha de este equipo salieron.',
            'origenes.*.cantidad.required'   => 'Indica cuántas salieron de este equipo.',
            'origenes.*.cantidad.min'        => 'Indica cuántas salieron de este equipo.',
        ];
    }

    public function guardar(): void
    {
        $this->authorize('create', ComponenteAlmacen::class);

        // El Analista solo registra en su empresa activa.
        $actor = Auth::user();
        if (! $actor->hasRole('Administrador')) {
            $this->empresa_id = (string) ($actor->empresa_activa_id ?? '');
        }

        $this->origenes = array_values($this->origenes);
        $usada = ! $this->familia?->esAGranel() && $this->condicion === PiezaExtraida::CONDICION_REUTILIZADO;

        $this->validate();

        $familia = $this->familia;

        // Usada: cada origen tiene que ser un equipo de la empresa, sin repetir.
        $equipos = collect();
        if ($usada) {
            $equipos = $this->equiposDeOrigen();
            if ($equipos === null) {
                return;
            }
        }

        if ($this->desdeFichaDelEquipo) {
            $this->guardarDesdeFichaDelEquipo($actor, $equipos);
            return;
        }

        if ($usada) {
            $this->cantidad = (int) collect($this->origenes)->sum(fn ($o) => (int) $o['cantidad']);
        }

        try {
            $componente = DB::transaction(function () use ($familia, $actor, $usada, $equipos) {
                $atributo = $this->usaFicha ? $familia->atributoRepresentativo() : null;
                $valores  = $this->usaFicha
                    ? collect($familia->campos)->mapWithKeys(fn ($c) => [$c['id'] => trim((string) ($this->valores[$c['id']] ?? '')) ?: null])->all()
                    : null;

                $componente = $this->resolverVariante($familia, $atributo, $valores, $actor);
                $this->authorize('registrarMovimiento', $componente);

                if ($familia->esAGranel()) {
                    $componente->registrarEntrada($this->cantidad, $actor, $this->motivo ?: 'Entrada de stock', $this->observaciones ?: null);
                } elseif ($usada) {
                    // Una entrada por equipo de origen: cada pieza queda con SU equipo.
                    $ids = [];
                    foreach ($this->origenes as $n => $o) {
                        try {
                            $ids = array_merge($ids, app(PiezaManualService::class)->registrar(
                                $componente->fresh(),
                                (int) $o['cantidad'],
                                $this->condicion,
                                $actor,
                                equipoOrigen: $equipos[$n],
                                observaciones: $this->observaciones ?: null,
                                seriales: preg_split('/\r\n|\r|\n/', (string) $o['seriales']) ?: [],
                                atributo: $atributo,
                                valorFicha: $valores,
                            )->pluck('id')->all());
                        } catch (\InvalidArgumentException $e) {
                            throw new \App\Exceptions\ErrorEnCampoException("origenes.{$n}.seriales", $e->getMessage());
                        }
                    }
                    $this->recienRegistradas = $ids;
                } else {
                    $this->recienRegistradas = app(PiezaManualService::class)->registrar(
                        $componente,
                        $this->cantidad,
                        $this->condicion,
                        $actor,
                        observaciones: $this->observaciones ?: null,
                        seriales: preg_split('/\r\n|\r|\n/', $this->seriales) ?: [],
                        atributo: $atributo,
                        valorFicha: $valores,
                    )->pluck('id')->all();
                }

                return $componente;
            });

            $msg = "Entraron {$this->cantidad} a «{$componente->nombre}».";

            $esperando = MantenimientoComponente::pendientesVisiblesPara($actor)->where('componente_id', $componente->id)->count();
            if ($esperando > 0) {
                $msg .= " Hay {$esperando} solicitud(es) pendiente(s) de esta variante: entrégalas desde «Solicitudes pendientes».";
            }

            if ($usada) {
                $msg = "Entraron {$this->cantidad} usada(s) a «{$componente->nombre}»: {$this->resumenOrigenes}.";
            }

            $this->terminar($msg);
        } catch (\App\Exceptions\ErrorEnCampoException $e) {
            // Error en los seriales de un origen concreto: se muestra en ese origen.
            $this->addError($e->campo, $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->addError('seriales', $e->getMessage());
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('RegistrarEntradaModal@guardar: ' . $e->getMessage());
            $this->addError('seriales', 'Ocurrió un error y no se registró nada. Inténtalo de nuevo.');
        }
    }

    /**
     * Los equipos de cada origen (mismo orden). Cada uno tiene que ser de la
     * empresa y no repetirse. Null si hay error (ya queda marcado en el origen).
     */
    private function equiposDeOrigen(): ?Collection
    {
        $equipos = collect();
        $vistos  = [];

        foreach ($this->origenes as $n => $o) {
            $equipo = Equipo::with('categoria')->where('empresa_id', $this->empresa_id)->find((int) $o['equipo']);
            if (! $equipo) {
                $this->addError("origenes.{$n}.equipo", 'Ese equipo no pertenece a la empresa.');
                return null;
            }
            if (isset($vistos[$equipo->id])) {
                $this->addError("origenes.{$n}.equipo", "{$equipo->codigo_interno} ya está arriba: agrega ahí lo que salió de él.");
                return null;
            }
            $vistos[$equipo->id] = true;
            $equipos[$n] = $equipo;
        }

        return $equipos;
    }

    /**
     * Usada de una familia con ficha: en cada equipo de origen, lo marcado
     * de su ficha sale del equipo (su ficha se actualiza) y entra al almacén
     * como pieza usada, con su serial si se escribió. Todo o nada.
     */
    private function guardarDesdeFichaDelEquipo($actor, Collection $equipos): void
    {
        $plan = [];   // [equipo, [instancias en orden], [seriales]]

        foreach ($this->origenes as $n => $o) {
            $equipo   = $equipos[$n];
            $atributo = $this->atributoEn($equipo);

            if (! $atributo) {
                $this->addError("origenes.{$n}.equipo", "La categoría de {$equipo->codigo_interno} no tiene {$this->familia->nombre} vinculada a la familia.");
                return;
            }

            $ids = collect($o['instancias'])->map(fn ($id) => (int) $id)->unique()->values();
            $instancias = \App\Models\EquipoAtributoGrupoInstancia::where('equipo_id', $equipo->id)
                ->where('atributo_id', $atributo->id)
                ->where('es_actual', true)
                ->whereIn('id', $ids)
                ->get()
                ->sortBy(fn ($i) => $ids->search($i->id))
                ->values();

            if ($instancias->count() !== $ids->count()) {
                $this->addError("origenes.{$n}.instancias", 'Alguna de las marcadas ya no está en la ficha del equipo. Vuelve a elegir.');
                unset($this->detalleOrigenes);
                return;
            }

            $seriales = collect(preg_split('/\r\n|\r|\n/', (string) $o['seriales']) ?: [])->map(fn ($s) => trim($s))->filter()->values();
            if ($seriales->count() > $instancias->count()) {
                $this->addError("origenes.{$n}.seriales", "Escribiste {$seriales->count()} seriales para {$instancias->count()} pieza(s).");
                return;
            }

            $plan[] = [$equipo, $instancias, $seriales];
        }

        try {
            $piezas = DB::transaction(fn () => collect($plan)->flatMap(fn ($p) => $p[1]->map(
                fn ($instancia, $k) => app(ExtraccionPiezaService::class)->extraerDeGrupoInstancia(
                    $p[0], $instancia, PiezaExtraida::ESTADO_EN_ALMACEN, $actor, PiezaExtraida::MOTIVO_DESARME_MANUAL,
                    observaciones: $this->observaciones ?: null,
                    serial: $p[2][$k] ?? null,
                ))));
        } catch (\InvalidArgumentException $e) {
            $this->addError('origenes.0.instancias', $e->getMessage());
            return;
        }

        $this->recienRegistradas = $piezas->pluck('id')->all();
        $this->terminar("Entraron {$piezas->count()} pieza(s) usada(s): {$this->resumenOrigenes}. Sus fichas ya no las tienen.");
    }

    /** Después de guardar: si entraron piezas con código, se ofrece imprimir sus etiquetas. */
    private function terminar(string $msg): void
    {
        $this->dispatch('stockActualizado');
        $this->dispatch('componenteGuardado');
        $this->dispatch('toast', type: 'success', message: $msg);

        if ($this->recienRegistradas) {
            $this->resumen = $msg;
            return; // el modal queda abierto con "Imprimir etiquetas"
        }

        $this->close();
    }

    /** URL de la hoja de etiquetas de lo que acaba de entrar. */
    #[Computed]
    public function urlEtiquetas(): ?string
    {
        return $this->recienRegistradas
            ? route('admin.almacen.etiquetas', ['piezas' => implode(',', $this->recienRegistradas)])
            : null;
    }

    public function close(): void
    {
        $this->open = false;
        $this->resetValidation();
    }

    // ── Internos ─────────────────────────────────────────────────────────────

    private function resolverVariante(FamiliaComponente $familia, $atributo, ?array $valores, $actor): ComponenteAlmacen
    {
        if ($atributo) {
            return app(ExtraccionPiezaService::class)->varianteDeFicha((int) $this->empresa_id, $atributo, $valores, $actor);
        }

        if ($this->componenteId === 'nueva') {
            return ComponenteAlmacen::create([
                'empresa_id'   => $this->empresa_id,
                'familia_id'   => $familia->id,
                'nombre'       => trim($this->nuevoNombre),
                'unidad'       => trim($this->nuevaUnidad) ?: 'unidad',
                'stock_actual' => 0,
                'activo'       => true,
                'creado_por'   => $actor->id,
            ]);
        }

        return ComponenteAlmacen::where('empresa_id', $this->empresa_id)
            ->where('familia_id', $familia->id)
            ->findOrFail($this->componenteId);
    }

    /** Valores de ficha de una variante existente (de cualquiera de sus piezas). */
    private function valoresDeVariante(ComponenteAlmacen $componente): array
    {
        return $componente->valoresFicha();
    }

    public function render()
    {
        return view('livewire.almacen.registrar-entrada-modal');
    }
}
