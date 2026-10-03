<?php

namespace App\Livewire\Cronograma;

use App\Models\CategoriaEquipo;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\PlanMantenimiento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PlanesMantenimientoTable extends Component
{
    use WithPagination;

    #[Url(as: 'empresa')]
    public string $empresa_id = '';

    public string $categoria_id      = '';
    public string $departamento_id   = '';
    public bool   $mostrar_inactivos = false;

    /** '' (todos) | 'vencido' | 'hoy' | 'proximo' | 'programado' — ver queryFiltrada(). */
    public string $estadoFecha = '';

    public function mount(): void
    {
        $this->authorize('viewAny', PlanMantenimiento::class);

        $actor = Auth::user();
        if (! $actor->hasRole('Administrador')) {
            $this->empresa_id = (string) ($actor->empresa_activa_id ?? '');
        }
    }

    #[On('planGuardado')]
    #[On('planEliminado')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    /**
     * Borra el plan (soft delete) — deja de generar lotes nuevos. Los casos
     * ya generados no se tocan: siguen existiendo y se procesan igual.
     */
    public function eliminar(int $id): void
    {
        try {
            $plan = PlanMantenimiento::findOrFail($id);
            $this->authorize('delete', $plan);

            $nombre = "{$plan->categoria->nombre} — {$plan->empresa->nombre}";
            $plan->delete();

            $this->dispatch('planEliminado');
            $this->dispatch('toast', type: 'success', message: "Plan «{$nombre}» eliminado.");
        } catch (\Exception $e) {
            Log::error('PlanesMantenimientoTable@eliminar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al eliminar el plan.');
        }
    }

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    /** Cambiar la empresa invalida cualquier departamento elegido (pertenece a otra empresa). */
    public function updatedEmpresaId(): void
    {
        $this->departamento_id = '';
    }

    public function resetFilters(): void
    {
        $actor = Auth::user();
        $this->reset(['categoria_id', 'departamento_id', 'mostrar_inactivos', 'estadoFecha']);
        $this->empresa_id = $actor->hasRole('Administrador') ? '' : (string) ($actor->empresa_activa_id ?? '');
        $this->resetPage();
    }

    #[Computed]
    public function empresasOpciones()
    {
        $actor = Auth::user();

        if ($actor->hasRole('Administrador')) {
            return Empresa::orderBy('nombre')->pluck('nombre', 'id');
        }

        return Empresa::where('id', $actor->empresa_activa_id)->pluck('nombre', 'id');
    }

    #[Computed]
    public function categoriasOpciones()
    {
        return CategoriaEquipo::orderBy('nombre')->pluck('nombre', 'id');
    }

    #[Computed]
    public function departamentosOpciones()
    {
        return Departamento::activos()
            ->where(fn ($q) => $q->whereNull('empresa_id')->when($this->empresa_id, fn ($q2) => $q2->orWhere('empresa_id', $this->empresa_id)))
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        $actor = Auth::user();

        return collect([
            $this->categoria_id !== '',
            $this->departamento_id !== '',
            $this->mostrar_inactivos,
            $actor->hasRole('Administrador') && $this->empresa_id !== '',
        ])->filter()->count();
    }

    private function baseQuery()
    {
        return PlanMantenimiento::visiblePara(Auth::user())->activos();
    }

    #[Computed]
    public function totalVencidos(): int
    {
        return $this->baseQuery()->where('fecha_proximo', '<', now()->toDateString())->count();
    }

    #[Computed]
    public function totalProximos(): int
    {
        return $this->baseQuery()
            ->whereBetween('fecha_proximo', [now()->toDateString(), now()->addDays(PlanMantenimiento::DIAS_ANTELACION_GENERACION)->toDateString()])
            ->count();
    }

    #[Computed]
    public function totalAlDia(): int
    {
        return $this->baseQuery()
            ->where('fecha_proximo', '>', now()->addDays(PlanMantenimiento::DIAS_ANTELACION_GENERACION)->toDateString())
            ->count();
    }

    /**
     * Filtros base compartidos por el listado y por las pestañas de estado:
     * todo MENOS estadoFecha — así cada pestaña puede mostrar su propio
     * conteo sin heredar la pestaña actualmente seleccionada.
     */
    private function queryFiltrada()
    {
        return PlanMantenimiento::visiblePara(Auth::user())
            ->when(! $this->mostrar_inactivos, fn ($q) => $q->where('activo', true))
            ->when($this->empresa_id, fn ($q) => $q->where('empresa_id', $this->empresa_id))
            ->when($this->categoria_id, fn ($q) => $q->where('categoria_id', $this->categoria_id))
            ->when($this->departamento_id, fn ($q) => $q->where('departamento_id', $this->departamento_id));
    }

    /**
     * Los mismos 4 estados que ve cada fila (Vencido/Es hoy/Próximo/
     * Programado — ver PlanMantenimiento::estaVencido()/esHoy()/
     * estaProximo()), para poder filtrar el listado por pestaña igual que
     * ya se hace en Mantenimientos.
     */
    #[Computed]
    public function conteosPorEstadoFecha(): array
    {
        $hoy  = now()->toDateString();
        $fin  = now()->addDays(PlanMantenimiento::DIAS_ANTELACION_GENERACION)->toDateString();
        $base = $this->queryFiltrada();

        return [
            'vencido'    => (clone $base)->whereDate('fecha_proximo', '<', $hoy)->count(),
            'hoy'        => (clone $base)->whereDate('fecha_proximo', $hoy)->count(),
            'proximo'    => (clone $base)->whereDate('fecha_proximo', '>', $hoy)->whereDate('fecha_proximo', '<=', $fin)->count(),
            'programado' => (clone $base)->whereDate('fecha_proximo', '>', $fin)->count(),
        ];
    }

    #[Computed]
    public function planes()
    {
        $hoy = now()->toDateString();
        $fin = now()->addDays(PlanMantenimiento::DIAS_ANTELACION_GENERACION)->toDateString();

        return $this->queryFiltrada()
            ->with(['categoria', 'empresa', 'departamento'])
            ->when($this->estadoFecha === 'vencido', fn ($q) => $q->whereDate('fecha_proximo', '<', $hoy))
            ->when($this->estadoFecha === 'hoy', fn ($q) => $q->whereDate('fecha_proximo', $hoy))
            ->when($this->estadoFecha === 'proximo', fn ($q) => $q->whereDate('fecha_proximo', '>', $hoy)->whereDate('fecha_proximo', '<=', $fin))
            ->when($this->estadoFecha === 'programado', fn ($q) => $q->whereDate('fecha_proximo', '>', $fin))
            ->orderBy('fecha_proximo')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.cronograma.planes-mantenimiento-table');
    }
}
