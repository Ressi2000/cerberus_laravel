<?php

namespace App\Livewire\Cronograma;

use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\PlanMantenimiento;
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
    public string $equipo_id        = '';
    public ?int   $frecuencia_meses = null;
    public string $fecha_proximo    = '';
    public string $observaciones    = '';
    public bool   $activo           = true;

    public array  $checklist            = [];
    public string $nuevaTareaChecklist   = '';

    #[On('openPlanCrear')]
    public function abrirCrear(): void
    {
        $this->authorize('create', PlanMantenimiento::class);

        $this->reset(['planId', 'equipo_id', 'frecuencia_meses', 'observaciones']);
        $this->activo = true;
        $this->fecha_proximo = now()->addMonths(1)->format('Y-m-d');

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
        $this->equipo_id        = (string) $plan->equipo_id;
        $this->frecuencia_meses = $plan->frecuencia_meses;
        $this->fecha_proximo    = $plan->fecha_proximo->format('Y-m-d');
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
        $this->checklist = collect(Mantenimiento::CHECKLIST_PREVENTIVO_DEFAULT)
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

    /** Equipos de la empresa elegida sin un plan activo todavía (o el propio, si se está editando). */
    #[Computed]
    public function equiposOpciones()
    {
        if (! $this->empresa_id) {
            return collect();
        }

        return Equipo::with('categoria')
            ->where('empresa_id', $this->empresa_id)
            ->where('activo', true)
            ->whereDoesntHave('planMantenimiento', function ($q) {
                if ($this->planId) {
                    $q->where('id', '!=', $this->planId);
                }
            })
            ->orderBy('codigo_interno')
            ->get()
            ->mapWithKeys(fn ($e) => [
                $e->id => trim(($e->codigo_interno ?? "Equipo #{$e->id}") . ' — ' . ($e->categoria->nombre ?? '')),
            ]);
    }

    protected function rules(): array
    {
        return [
            'empresa_id'       => 'required|exists:empresas,id',
            'equipo_id'        => 'required|exists:equipos,id',
            'frecuencia_meses' => 'required|integer|min:1|max:60',
            'fecha_proximo'    => 'required|date',
            'observaciones'    => 'nullable|string|max:1000',
        ];
    }

    protected function messages(): array
    {
        return [
            'equipo_id.required'        => 'Selecciona un equipo.',
            'frecuencia_meses.required'  => 'Indica cada cuántos meses se repite.',
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        try {
            $data = [
                'empresa_id'       => $this->empresa_id,
                'equipo_id'        => $this->equipo_id,
                'frecuencia_meses' => $this->frecuencia_meses,
                'fecha_proximo'    => $this->fecha_proximo,
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
                PlanMantenimiento::create(array_merge($data, ['activo' => true, 'creado_por' => Auth::id()]));
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
        $this->reset(['planId', 'empresa_id', 'equipo_id', 'frecuencia_meses', 'fecha_proximo', 'observaciones', 'checklist', 'nuevaTareaChecklist']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.cronograma.plan-mantenimiento-modal');
    }
}
