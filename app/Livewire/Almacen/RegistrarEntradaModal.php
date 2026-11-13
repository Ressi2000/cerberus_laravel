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
    public string $equipoOrigenId = '';
    public string $seriales       = '';
    public string $motivo         = '';
    public string $observaciones  = '';

    /**
     * Usada, de una familia con ficha: cuáles entradas de la ficha del
     * equipo de origen son las que salieron (ids de instancia). Así solo se
     * puede declarar algo que el equipo de verdad tiene, y su ficha se
     * actualiza (deja de tenerlo).
     */
    public array $instanciasOrigen = [];

    /** Piezas que acaban de entrar (para imprimir sus etiquetas). */
    public array $recienRegistradas = [];
    public string $resumen = '';

    #[On('openRegistrarEntrada')]
    public function abrir(?int $componenteId = null, ?int $familiaId = null): void
    {
        $this->authorize('create', ComponenteAlmacen::class);

        $this->reset(['familiaId', 'componenteId', 'nuevoNombre', 'valores', 'equipoOrigenId', 'seriales', 'motivo', 'observaciones', 'instanciasOrigen', 'recienRegistradas', 'resumen']);
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

    #[Computed]
    public function equipoOrigen(): ?Equipo
    {
        return $this->equipoOrigenId && $this->empresa_id
            ? Equipo::with('categoria')->where('empresa_id', $this->empresa_id)->find($this->equipoOrigenId)
            : null;
    }

    /** La característica de la categoría del equipo de origen que usa esta familia (ej. la RAM de Laptop). */
    #[Computed]
    public function atributoEnEquipo(): ?\App\Models\AtributoEquipo
    {
        return $this->equipoOrigen && $this->familia
            ? \App\Models\AtributoEquipo::where('categoria_id', $this->equipoOrigen->categoria_id)->where('familia_id', $this->familia->id)->first()
            : null;
    }

    /** Lo que la ficha del equipo de origen dice que tiene de esta familia. */
    #[Computed]
    public function fichaDelEquipo()
    {
        if (! $this->atributoEnEquipo) {
            return collect();
        }

        return \App\Models\EquipoAtributoGrupoInstancia::where('equipo_id', $this->equipoOrigen->id)
            ->where('atributo_id', $this->atributoEnEquipo->id)
            ->where('es_actual', true)
            ->orderBy('orden')
            ->get()
            ->map(fn ($i) => (object) ['id' => $i->id, 'descripcion' => $this->atributoEnEquipo->describirValor($i->valores ?? [])]);
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

    public function updatedEquipoOrigenId(): void
    {
        $this->instanciasOrigen = [];
        unset($this->equipoOrigen, $this->atributoEnEquipo, $this->fichaDelEquipo);
    }

    public function updatedCondicion(): void
    {
        $this->instanciasOrigen = [];
    }

    public function updatedEmpresaId(): void
    {
        $this->componenteId = '';
        $this->equipoOrigenId = '';
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
            // Los valores salen de la ficha del equipo: solo hay que elegir cuáles.
            $rules['instanciasOrigen']   = 'required|array|min:1';
            $rules['instanciasOrigen.*'] = 'integer';
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
            if ($this->condicion === PiezaExtraida::CONDICION_REUTILIZADO) {
                $rules['equipoOrigenId'] = 'required';
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
            'equipoOrigenId.required' => 'Indica de qué equipo salió la pieza usada.',
            'valores.*.required'      => 'Completa este campo de la ficha.',
            'instanciasOrigen.required' => 'Marca cuál (o cuáles) de la ficha del equipo salieron.',
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

        if ($this->desdeFichaDelEquipo) {
            $this->cantidad = max(1, count($this->instanciasOrigen));
        }

        $this->validate();

        $familia = $this->familia;

        if ($this->desdeFichaDelEquipo) {
            $this->guardarDesdeFichaDelEquipo($actor);
            return;
        }

        $equipoOrigen = null;
        if (! $familia->esAGranel() && $this->condicion === PiezaExtraida::CONDICION_REUTILIZADO) {
            $equipoOrigen = Equipo::where('empresa_id', $this->empresa_id)->find($this->equipoOrigenId);
            if (! $equipoOrigen) {
                $this->addError('equipoOrigenId', 'Ese equipo no pertenece a la empresa.');
                return;
            }
        }

        try {
            $componente = DB::transaction(function () use ($familia, $actor, $equipoOrigen) {
                $atributo = $this->usaFicha ? $familia->atributoRepresentativo() : null;
                $valores  = $this->usaFicha
                    ? collect($familia->campos)->mapWithKeys(fn ($c) => [$c['id'] => trim((string) ($this->valores[$c['id']] ?? '')) ?: null])->all()
                    : null;

                $componente = $this->resolverVariante($familia, $atributo, $valores, $actor);
                $this->authorize('registrarMovimiento', $componente);

                if ($familia->esAGranel()) {
                    $componente->registrarEntrada($this->cantidad, $actor, $this->motivo ?: 'Entrada de stock', $this->observaciones ?: null);
                } else {
                    $this->recienRegistradas = app(PiezaManualService::class)->registrar(
                        $componente,
                        $this->cantidad,
                        $this->condicion,
                        $actor,
                        equipoOrigen: $equipoOrigen,
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

            $this->terminar($msg);
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
     * Usada de una familia con ficha: cada entrada marcada de la ficha del
     * equipo de origen sale del equipo (su ficha se actualiza) y entra al
     * almacén como pieza usada, con su serial si se escribió.
     */
    private function guardarDesdeFichaDelEquipo($actor): void
    {
        $equipo   = $this->equipoOrigen;
        $atributo = $this->atributoEnEquipo;

        if (! $equipo || ! $atributo) {
            $this->addError('equipoOrigenId', 'Elige un equipo cuya ficha tenga ' . ($this->familia->nombre ?? 'esta pieza') . '.');
            return;
        }

        $ids = collect($this->instanciasOrigen)->map(fn ($id) => (int) $id)->unique()->values();
        $instancias = \App\Models\EquipoAtributoGrupoInstancia::where('equipo_id', $equipo->id)
            ->where('atributo_id', $atributo->id)
            ->where('es_actual', true)
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($i) => $ids->search($i->id));

        if ($instancias->count() !== $ids->count()) {
            $this->addError('instanciasOrigen', 'Alguna de las marcadas ya no está en la ficha del equipo. Vuelve a elegir.');
            unset($this->fichaDelEquipo);
            return;
        }

        $seriales = collect(preg_split('/\r\n|\r|\n/', $this->seriales) ?: [])->map(fn ($s) => trim($s))->filter()->values();
        if ($seriales->count() > $instancias->count()) {
            $this->addError('seriales', "Escribiste {$seriales->count()} seriales para {$instancias->count()} pieza(s).");
            return;
        }

        try {
            $piezas = DB::transaction(fn () => $instancias->values()->map(fn ($instancia, $n) => app(ExtraccionPiezaService::class)->extraerDeGrupoInstancia(
                $equipo, $instancia, PiezaExtraida::ESTADO_EN_ALMACEN, $actor, PiezaExtraida::MOTIVO_DESARME_MANUAL,
                observaciones: $this->observaciones ?: null,
                serial: $seriales[$n] ?? null,
            )));
        } catch (\InvalidArgumentException $e) {
            $this->addError('instanciasOrigen', $e->getMessage());
            return;
        }

        $this->recienRegistradas = $piezas->pluck('id')->all();
        $this->terminar("Entraron {$piezas->count()} pieza(s) usada(s) de {$equipo->codigo_interno}; su ficha ya no las tiene.");
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
