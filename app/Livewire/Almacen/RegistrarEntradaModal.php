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

    #[On('openRegistrarEntrada')]
    public function abrir(?int $componenteId = null, ?int $familiaId = null): void
    {
        $this->authorize('create', ComponenteAlmacen::class);

        $this->reset(['familiaId', 'componenteId', 'nuevoNombre', 'valores', 'equipoOrigenId', 'seriales', 'motivo', 'observaciones']);
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

        if ($this->usaFicha) {
            foreach ($this->familia->campos as $campo) {
                $rules["valores.{$campo['id']}"] = (($campo['requerido'] ?? false) ? 'required' : 'nullable') . '|string|max:255';
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
            'valores.*.required'      => 'Este campo de la ficha es obligatorio.',
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

        $this->validate();

        $familia = $this->familia;

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
                    app(PiezaManualService::class)->registrar(
                        $componente,
                        $this->cantidad,
                        $this->condicion,
                        $actor,
                        equipoOrigen: $equipoOrigen,
                        observaciones: $this->observaciones ?: null,
                        seriales: preg_split('/\r\n|\r|\n/', $this->seriales) ?: [],
                        atributo: $atributo,
                        valorFicha: $valores,
                    );
                }

                return $componente;
            });

            $msg = "Entraron {$this->cantidad} a «{$componente->nombre}».";

            $esperando = MantenimientoComponente::pendientesVisiblesPara($actor)->where('componente_id', $componente->id)->count();
            if ($esperando > 0) {
                $msg .= " Hay {$esperando} solicitud(es) pendiente(s) de esta variante: entrégalas desde «Solicitudes pendientes».";
            }

            $this->close();
            $this->dispatch('stockActualizado');
            $this->dispatch('componenteGuardado');
            $this->dispatch('toast', type: 'success', message: $msg);
        } catch (\InvalidArgumentException $e) {
            $this->addError('seriales', $e->getMessage());
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('RegistrarEntradaModal@guardar: ' . $e->getMessage());
            $this->addError('seriales', 'Ocurrió un error y no se registró nada. Inténtalo de nuevo.');
        }
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
    /**
     * Ficha de una variante, para no tener que volver a escribirla (ej. al
     * dar entrada a lo que se solicitó desde un caso): de una de sus piezas
     * o, si todavía no tiene, de su nombre (ver FamiliaComponente::valoresDesdeNombre).
     */
    private function valoresDeVariante(ComponenteAlmacen $componente): array
    {
        $familia = $componente->familia;
        if (! $familia?->tieneFicha()) {
            return [];
        }

        $campos = collect($familia->campos)->pluck('id');
        $pieza  = PiezaExtraida::where('componente_almacen_id', $componente->id)->whereNotNull('valor_extraido')->latest('id')->first();
        $valores = collect($pieza?->valor_extraido ?? [])->only($campos)->filter(fn ($v) => $v !== null && $v !== '');

        if ($valores->isEmpty()) {
            $valores = collect($familia->valoresDesdeNombre($componente->nombre));
        }

        return $valores->map(fn ($v) => (string) $v)->all();
    }

    public function render()
    {
        return view('livewire.almacen.registrar-entrada-modal');
    }
}
