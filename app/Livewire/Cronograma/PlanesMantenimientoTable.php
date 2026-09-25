<?php

namespace App\Livewire\Cronograma;

use App\Models\Empresa;
use App\Models\PlanMantenimiento;
use Illuminate\Support\Facades\Auth;
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

    public string $search           = '';
    public bool   $mostrar_inactivos = false;

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

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $actor = Auth::user();
        $this->reset(['search', 'mostrar_inactivos']);
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
    public function activeFiltersCount(): int
    {
        $actor = Auth::user();

        return collect([
            $this->search !== '',
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

    #[Computed]
    public function planes()
    {
        return PlanMantenimiento::with(['equipo.categoria', 'empresa', 'casoAbierto'])
            ->visiblePara(Auth::user())
            ->when(! $this->mostrar_inactivos, fn ($q) => $q->where('activo', true))
            ->when($this->empresa_id, fn ($q) => $q->where('empresa_id', $this->empresa_id))
            ->when($this->search, fn ($q) => $q->whereHas('equipo', fn ($q) =>
                $q->where('codigo_interno', 'like', "%{$this->search}%")
            ))
            ->orderBy('fecha_proximo')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.cronograma.planes-mantenimiento-table');
    }
}
