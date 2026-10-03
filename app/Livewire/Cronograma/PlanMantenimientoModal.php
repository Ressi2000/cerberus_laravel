<?php

namespace App\Livewire\Cronograma;

use App\Models\CategoriaEquipo;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\PlanMantenimiento;
use App\Models\TareaMantenimientoCatalogo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PlanMantenimientoModal extends Component
{
    public bool   $open   = false;
    public ?int   $planId = null;

    public string $empresa_id       = '';
    public string $categoria_id     = '';
    public string $departamento_id  = '';
    public ?int   $frecuencia_meses = null;
    public string $fecha_proximo    = '';
    public ?int   $duracion_dias_estimada = 1;
    public string $responsable_id   = '';
    public string $observaciones    = '';
    public bool   $activo           = true;

    public array  $checklist            = [];

    /** Selección explícita de equipos individuales (vacío = todos los que calcen con categoría/departamento). */
    public array  $equiposSeleccionados = [];

    #[On('openPlanCrear')]
    public function abrirCrear(): void
    {
        $this->authorize('create', PlanMantenimiento::class);

        $this->reset(['planId', 'categoria_id', 'departamento_id', 'frecuencia_meses', 'observaciones', 'responsable_id', 'equiposSeleccionados']);
        $this->activo = true;
        $this->fecha_proximo = now()->addMonths(1)->format('Y-m-d');
        $this->duracion_dias_estimada = 1;

        $actor = Auth::user();
        $this->empresa_id = $actor->hasRole('Analista') ? (string) ($actor->empresa_activa_id ?? '') : '';

        $this->cargarChecklistDefault();
        $this->resetValidation();
        $this->open = true;
    }

    #[On('openPlanEditar')]
    public function abrirEditar(int $id): void
    {
        $plan = PlanMantenimiento::findOrFail($id);
        $this->authorize('update', $plan);

        $this->planId           = $plan->id;
        $this->empresa_id       = (string) $plan->empresa_id;
        $this->categoria_id     = (string) $plan->categoria_id;
        $this->departamento_id  = (string) ($plan->departamento_id ?? '');
        $this->frecuencia_meses = $plan->frecuencia_meses;
        $this->fecha_proximo    = $plan->fecha_proximo->format('Y-m-d');
        $this->duracion_dias_estimada = $plan->duracion_dias_estimada;
        $this->responsable_id   = (string) ($plan->responsable_id ?? '');
        $this->observaciones    = $plan->observaciones ?? '';
        $this->activo           = $plan->activo;
        $this->checklist = collect($plan->checklist_plantilla ?: [])
            ->map(fn ($tarea) => ['tarea' => $tarea, 'incluir' => true])
            ->toArray();
        $this->equiposSeleccionados = $plan->equipos()->pluck('equipos.id')->map(fn ($id) => (string) $id)->toArray();

        $this->resetValidation();
        $this->open = true;
    }

    private function cargarChecklistDefault(): void
    {
        $this->checklist = TareaMantenimientoCatalogo::activas()->ordenadas()->pluck('nombre')
            ->map(fn ($tarea) => ['tarea' => $tarea, 'incluir' => true])
            ->toArray();
    }

    /** Cambiar el alcance (empresa/categoría/departamento) invalida cualquier selección puntual de equipos previa. */
    public function updatedEmpresaId(): void
    {
        $this->equiposSeleccionados = [];
    }

    public function updatedCategoriaId(): void
    {
        $this->equiposSeleccionados = [];
    }

    public function updatedDepartamentoId(): void
    {
        $this->equiposSeleccionados = [];
    }

    #[Computed]
    public function responsablesOpciones()
    {
        return User::whereIn('id', function ($q) {
            $q->select('model_id')
              ->from('model_has_roles')
              ->whereIn('role_id', function ($q2) {
                  $q2->select('id')->from('roles')->whereIn('name', ['Administrador', 'Analista']);
              });
        })->orderBy('name')->pluck('name', 'id');
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

    #[Computed]
    public function categoriasOpciones()
    {
        return CategoriaEquipo::activos()->orderBy('nombre')->pluck('nombre', 'id');
    }

    #[Computed]
    public function departamentosOpciones()
    {
        return Departamento::activos()
            ->where(fn ($q) => $q->whereNull('empresa_id')->when($this->empresa_id, fn ($q2) => $q2->orWhere('empresa_id', $this->empresa_id)))
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    /**
     * Ids de equipos que NO conviene ofrecer en "equipos puntuales" porque
     * ya están comprometidos — por dos motivos distintos:
     *
     *   1. Otro plan (misma empresa+categoría) ya los alcanza — evita que
     *      dos planes choquen por el mismo equipo (ver
     *      detectarConflictoEquipos(): un plan sin departamento cubre TODOS
     *      los departamentos, así que se compara el alcance real de cada
     *      plan, no solo si el departamento_id coincide).
     *   2. El equipo ya tiene un caso abierto (Preventivo o Correctivo) de
     *      CUALQUIER origen — GenerarMantenimientosProgramados jamás va a
     *      generarle un caso nuevo mientras el actual siga abierto, así que
     *      no tiene sentido dejarlo elegir y enterarse recién al generar el
     *      lote. Los casos que pertenecen al plan que se está editando NO
     *      cuentan acá — son su propio trabajo en curso, no un impedimento.
     */
    #[Computed]
    public function equiposYaCubiertos()
    {
        if (! $this->empresa_id || ! $this->categoria_id) {
            return collect();
        }

        $porOtroPlan = PlanMantenimiento::where('empresa_id', $this->empresa_id)
            ->where('categoria_id', $this->categoria_id)
            ->when($this->planId, fn ($q) => $q->where('id', '!=', $this->planId))
            ->get()
            ->flatMap(fn (PlanMantenimiento $otro) => $otro->equiposAlcanzados()->pluck('id'));

        $conCasoAbierto = Equipo::where('empresa_id', $this->empresa_id)
            ->where('categoria_id', $this->categoria_id)
            ->whereHas('mantenimientos', fn ($q) => $q
                ->whereNotIn('estado', Mantenimiento::ESTADOS_TERMINALES)
                ->when($this->planId, fn ($q2) => $q2->where(fn ($q3) => $q3
                    ->whereNull('plan_mantenimiento_id')
                    ->orWhere('plan_mantenimiento_id', '!=', $this->planId)
                ))
            )
            ->pluck('id');

        return $porOtroPlan->merge($conCasoAbierto)->unique()->values();
    }

    /**
     * Equipos activos que calzan con empresa+categoría+departamento,
     * EXCLUYENDO los que ya están comprometidos (otro plan, o un caso
     * abierto — ver equiposYaCubiertos()) — fuente de la selección puntual
     * opcional. Los equipos y casos que pertenecen al plan que se está
     * editando no se excluyen a sí mismos.
     */
    #[Computed]
    public function equiposDisponibles()
    {
        if (! $this->empresa_id || ! $this->categoria_id) {
            return collect();
        }

        return Equipo::with('categoria')
            ->where('empresa_id', $this->empresa_id)
            ->where('categoria_id', $this->categoria_id)
            ->where('activo', true)
            ->when($this->departamento_id, fn ($q) => $q->deDepartamento((int) $this->departamento_id))
            ->whereNotIn('id', $this->equiposYaCubiertos)
            ->orderBy('codigo_interno')
            ->get();
    }

    /** Cuántos equipos activos alcanzaría el plan (vista previa) con el alcance elegido. */
    #[Computed]
    public function equiposAlcanzadosCount(): ?int
    {
        if (! $this->empresa_id || ! $this->categoria_id) {
            return null;
        }

        if (! empty($this->equiposSeleccionados)) {
            return count($this->equiposSeleccionados);
        }

        return $this->equiposDisponibles->count();
    }

    /**
     * De los equipos que calzan con el alcance elegido, cuántos ya están
     * comprometidos (otro plan, o un caso abierto) y por eso no aparecen en
     * equiposDisponibles ni en equiposAlcanzadosCount — para avisarlo en
     * vez de dejar que la cuenta "se achique" sin explicación.
     */
    #[Computed]
    public function equiposExcluidosCount(): int
    {
        if (! $this->empresa_id || ! $this->categoria_id || $this->equiposYaCubiertos->isEmpty()) {
            return 0;
        }

        return Equipo::where('empresa_id', $this->empresa_id)
            ->where('categoria_id', $this->categoria_id)
            ->where('activo', true)
            ->when($this->departamento_id, fn ($q) => $q->deDepartamento((int) $this->departamento_id))
            ->whereIn('id', $this->equiposYaCubiertos)
            ->count();
    }

    protected function rules(): array
    {
        return [
            'empresa_id'       => 'required|exists:empresas,id',
            'categoria_id'     => 'required|exists:categorias_equipos,id',
            'departamento_id'  => 'nullable|exists:departamentos,id',
            'frecuencia_meses' => 'required|integer|min:1|max:60',
            'fecha_proximo'    => 'required|date',
            'duracion_dias_estimada' => 'required|integer|min:1|max:60',
            'responsable_id'   => 'nullable|exists:users,id',
            'observaciones'    => 'nullable|string|max:1000',
        ];
    }

    protected function messages(): array
    {
        return [
            'categoria_id.required'     => 'Selecciona una categoría.',
            'frecuencia_meses.required'  => 'Indica cada cuántos meses se repite.',
        ];
    }

    /**
     * Varios planes pueden compartir empresa+categoría (para dividir un
     * universo grande de equipos en tandas con fechas propias), pero nunca
     * dos planes activos pueden alcanzar el MISMO equipo — eso sí generaría
     * lotes duplicados. Se compara el ALCANCE REAL de equipos de cada plan
     * (equiposAlcanzados()), no si el departamento_id coincide: un plan sin
     * departamento cubre TODOS los departamentos, así que un plan "ST" y
     * uno "sin departamento" sí pueden chocar aunque sus departamento_id
     * sean distintos — y uno "ST" y otro "Contabilidad" nunca chocan,
     * aunque ambos sean "sin equipos puntuales", porque sus equipos reales
     * no se superponen.
     */
    private function detectarConflictoEquipos(): ?string
    {
        $otros = PlanMantenimiento::where('empresa_id', $this->empresa_id)
            ->where('categoria_id', $this->categoria_id)
            ->when($this->planId, fn ($q) => $q->where('id', '!=', $this->planId))
            ->get();

        if ($otros->isEmpty()) {
            return null;
        }

        $sinEquiposPuntuales = empty($this->equiposSeleccionados);

        $misEquipos = $sinEquiposPuntuales
            ? Equipo::where('empresa_id', $this->empresa_id)
                ->where('categoria_id', $this->categoria_id)
                ->where('activo', true)
                ->when($this->departamento_id, fn ($q) => $q->deDepartamento((int) $this->departamento_id))
                ->pluck('id')
            : collect($this->equiposSeleccionados)->map(fn ($id) => (int) $id);

        foreach ($otros as $otro) {
            $equiposOtro = $otro->equiposAlcanzados()->pluck('id');

            if ($misEquipos->intersect($equiposOtro)->isNotEmpty()) {
                return $sinEquiposPuntuales
                    ? "Ya existe un plan (#{$otro->id}) que cubre algunos de estos equipos. Para crear otro en paralelo, abre \"Elegir equipos puntuales\" y selecciona solo los que no estén ya en otro plan."
                    : "Algunos de los equipos elegidos ya están cubiertos por el plan #{$otro->id} de esta misma categoría.";
            }
        }

        return null;
    }

    public function guardar(): void
    {
        $this->validate();

        if ($conflicto = $this->detectarConflictoEquipos()) {
            $this->addError('categoria_id', $conflicto);
            return;
        }

        try {
            // El cronograma no programa trabajo en fin de semana: si se elige
            // sábado/domingo, se corre automáticamente al lunes siguiente.
            $fechaProximo = PlanMantenimiento::siguienteDiaHabil(\Carbon\Carbon::parse($this->fecha_proximo));

            $data = [
                'empresa_id'       => $this->empresa_id,
                'categoria_id'     => $this->categoria_id,
                'departamento_id'  => $this->departamento_id ?: null,
                'frecuencia_meses' => $this->frecuencia_meses,
                'fecha_proximo'    => $fechaProximo->toDateString(),
                'duracion_dias_estimada' => $this->duracion_dias_estimada,
                'responsable_id'   => $this->responsable_id ?: null,
                'observaciones'    => $this->observaciones ?: null,
                'checklist_plantilla' => collect($this->checklist)
                    ->filter(fn ($item) => $item['incluir'] ?? false)
                    ->pluck('tarea')
                    ->values()
                    ->toArray(),
            ];

            if ($this->planId) {
                $plan = PlanMantenimiento::findOrFail($this->planId);
                $this->authorize('update', $plan);
                $plan->update(array_merge($data, ['activo' => $this->activo]));
                $msg = 'Plan de mantenimiento actualizado.';
            } else {
                $this->authorize('create', PlanMantenimiento::class);

                // Siempre un plan nuevo, con su propio id. Antes, si había un
                // plan eliminado para esta misma categoría/empresa/
                // departamento, se reactivaba en silencio reutilizando su
                // fila — eso era necesario para no chocar con el unique que
                // existía en la base de datos. Ese unique ya no existe
                // (varios planes pueden compartir categoría/empresa/
                // departamento, ver detectarConflictoEquipos()), así que
                // reactivar ya no hace falta y además mezclaba el historial
                // de casos del plan eliminado con el plan "nuevo" sin que el
                // usuario se enterara.
                $plan = PlanMantenimiento::create(array_merge($data, ['activo' => true, 'creado_por' => Auth::id()]));

                $msg = 'Plan de mantenimiento creado.';
            }

            // Selección puntual de equipos (opcional): vacío = todos los que
            // calcen con categoría/departamento (comportamiento normal).
            $plan->equipos()->sync($this->equiposSeleccionados);

            if ($fechaProximo->toDateString() !== $this->fecha_proximo) {
                $msg .= " La fecha se corrió al lunes {$fechaProximo->format('d/m/Y')} (no se programa en fin de semana).";
            }

            $this->close();
            $this->dispatch('planGuardado');
            $this->dispatch('toast', type: 'success', message: $msg);
        } catch (\Exception $e) {
            Log::error('PlanMantenimientoModal@guardar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al guardar el plan.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['planId', 'empresa_id', 'categoria_id', 'departamento_id', 'frecuencia_meses', 'fecha_proximo', 'duracion_dias_estimada', 'responsable_id', 'observaciones', 'checklist', 'equiposSeleccionados']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.cronograma.plan-mantenimiento-modal');
    }
}
