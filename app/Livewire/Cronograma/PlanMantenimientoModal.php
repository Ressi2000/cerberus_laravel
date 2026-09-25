<?php

namespace App\Livewire\Cronograma;

use App\Models\CategoriaEquipo;
use App\Models\Empresa;
use App\Models\PlanMantenimiento;
use App\Models\TareaMantenimientoCatalogo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PlanMantenimientoModal extends Component
{
    public bool   $open   = false;
    public ?int   $planId = null;

    public string $empresa_id       = '';
    public string $categoria_id     = '';
    public ?int   $frecuencia_meses = null;
    public string $fecha_proximo    = '';
    public ?int   $duracion_dias_estimada = 1;
    public string $observaciones    = '';
    public bool   $activo           = true;

    public array  $checklist            = [];
    public string $nuevaTareaChecklist   = '';

    #[On('openPlanCrear')]
    public function abrirCrear(): void
    {
        $this->authorize('create', PlanMantenimiento::class);

        $this->reset(['planId', 'categoria_id', 'frecuencia_meses', 'observaciones']);
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
        $this->frecuencia_meses = $plan->frecuencia_meses;
        $this->fecha_proximo    = $plan->fecha_proximo->format('Y-m-d');
        $this->duracion_dias_estimada = $plan->duracion_dias_estimada;
        $this->observaciones    = $plan->observaciones ?? '';
        $this->activo           = $plan->activo;
        $this->checklist = collect($plan->checklist_plantilla ?: [])
            ->map(fn ($tarea) => ['tarea' => $tarea, 'incluir' => true])
            ->toArray();

        $this->resetValidation();
        $this->open = true;
    }

    private function cargarChecklistDefault(): void
    {
        $this->checklist = TareaMantenimientoCatalogo::activas()->ordenadas()->pluck('nombre')
            ->map(fn ($tarea) => ['tarea' => $tarea, 'incluir' => true])
            ->toArray();
    }

    public function agregarTareaChecklist(): void
    {
        $tarea = trim($this->nuevaTareaChecklist);
        if ($tarea === '') return;

        $this->checklist[] = ['tarea' => $tarea, 'incluir' => true];
        $this->nuevaTareaChecklist = '';
    }

    public function quitarTareaChecklist(int $index): void
    {
        unset($this->checklist[$index]);
        $this->checklist = array_values($this->checklist);
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

    /** Cuántos equipos activos de esta empresa+categoría alcanzaría el plan (vista previa). */
    #[Computed]
    public function equiposAlcanzadosCount(): ?int
    {
        if (! $this->empresa_id || ! $this->categoria_id) {
            return null;
        }

        return \App\Models\Equipo::where('empresa_id', $this->empresa_id)
            ->where('categoria_id', $this->categoria_id)
            ->where('activo', true)
            ->count();
    }

    protected function rules(): array
    {
        // whereNull('deleted_at'): un plan eliminado no debe bloquear crear
        // uno nuevo para la misma categoría/empresa — guardar() lo detecta
        // y lo reactiva en vez de chocar con el unique de la base de datos.
        $uniqueRule = Rule::unique('planes_mantenimiento', 'categoria_id')
            ->where(fn ($q) => $q->where('empresa_id', $this->empresa_id)->whereNull('deleted_at'))
            ->ignore($this->planId);

        return [
            'empresa_id'       => 'required|exists:empresas,id',
            'categoria_id'     => ['required', 'exists:categorias_equipos,id', $uniqueRule],
            'frecuencia_meses' => 'required|integer|min:1|max:60',
            'fecha_proximo'    => 'required|date',
            'duracion_dias_estimada' => 'required|integer|min:1|max:60',
            'observaciones'    => 'nullable|string|max:1000',
        ];
    }

    protected function messages(): array
    {
        return [
            'categoria_id.required'     => 'Selecciona una categoría.',
            'categoria_id.unique'       => 'Ya existe un plan para esta categoría en esta empresa.',
            'frecuencia_meses.required'  => 'Indica cada cuántos meses se repite.',
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        try {
            $data = [
                'empresa_id'       => $this->empresa_id,
                'categoria_id'     => $this->categoria_id,
                'frecuencia_meses' => $this->frecuencia_meses,
                'fecha_proximo'    => $this->fecha_proximo,
                'duracion_dias_estimada' => $this->duracion_dias_estimada,
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

                // Si había un plan eliminado para esta misma categoría/empresa,
                // se reactiva con los datos nuevos en vez de chocar con el
                // unique (empresa_id, categoria_id) de la base de datos.
                $eliminado = PlanMantenimiento::onlyTrashed()
                    ->where('empresa_id', $this->empresa_id)
                    ->where('categoria_id', $this->categoria_id)
                    ->first();

                if ($eliminado) {
                    $eliminado->restore();
                    $eliminado->update(array_merge($data, ['activo' => true]));
                } else {
                    PlanMantenimiento::create(array_merge($data, ['activo' => true, 'creado_por' => Auth::id()]));
                }

                $msg = 'Plan de mantenimiento creado.';
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
        $this->reset(['planId', 'empresa_id', 'categoria_id', 'frecuencia_meses', 'fecha_proximo', 'duracion_dias_estimada', 'observaciones', 'checklist', 'nuevaTareaChecklist']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.cronograma.plan-mantenimiento-modal');
    }
}
