<?php

namespace App\Livewire\Mantenimientos;

use App\Models\Empresa;
use App\Models\Mantenimiento;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado reactivo de Mantenimientos y Reparaciones.
 *
 * $soloReparacion=true es la vista "En Reparación" del sidebar: mismo
 * componente, mismos filtros, pero fijado a tipo=Correctivo y solo casos
 * abiertos (sin selector de tipo ni de "mostrar cerrados").
 */
class MantenimientosTable extends Component
{
    use WithPagination;

    public bool $soloReparacion = false;

    #[Url(as: 'empresa')]
    public string $empresa_id = '';

    public string $tipo             = '';
    public string $estado           = '';
    public bool   $mostrar_cerrados = false;
    public string $search           = '';

    public function mount(bool $soloReparacion = false): void
    {
        $this->authorize('viewAny', Mantenimiento::class);

        $this->soloReparacion = $soloReparacion;

        $actor = Auth::user();
        if (! $actor->hasRole('Administrador')) {
            $this->empresa_id = (string) ($actor->empresa_activa_id ?? '');
        }

        if ($this->soloReparacion) {
            $this->tipo = Mantenimiento::TIPO_CORRECTIVO;
        }
    }

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $actor = Auth::user();
        $this->reset(['search', 'estado', 'mostrar_cerrados']);
        $this->empresa_id = $actor->hasRole('Administrador') ? '' : (string) ($actor->empresa_activa_id ?? '');
        if (! $this->soloReparacion) {
            $this->tipo = '';
        }
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
            $this->estado !== '',
            $this->mostrar_cerrados,
            ! $this->soloReparacion && $this->tipo !== '',
            $actor->hasRole('Administrador') && $this->empresa_id !== '',
        ])->filter()->count();
    }

    #[Computed]
    public function totalAbiertos(): int
    {
        return $this->baseQuery()->abiertos()->count();
    }

    #[Computed]
    public function totalEsperandoComponente(): int
    {
        return $this->baseQuery()->abiertos()->where('esperando_componente', true)->count();
    }

    #[Computed]
    public function totalCorrectivos(): int
    {
        return $this->baseQuery()->correctivos()->abiertos()->count();
    }

    #[Computed]
    public function totalPreventivos(): int
    {
        return $this->baseQuery()->preventivos()->abiertos()->count();
    }

    private function baseQuery()
    {
        return Mantenimiento::visiblePara(Auth::user());
    }

    #[Computed]
    public function mantenimientos()
    {
        return Mantenimiento::with(['equipo.categoria', 'empresa', 'responsable'])
            ->visiblePara(Auth::user())
            ->when($this->soloReparacion, fn ($q) => $q->correctivos()->abiertos())
            ->when(! $this->soloReparacion, function ($q) {
                $q->when($this->tipo, fn ($q) => $q->where('tipo', $this->tipo))
                  ->when(! $this->mostrar_cerrados, fn ($q) => $q->abiertos());
            })
            ->when($this->empresa_id, fn ($q) => $q->where('empresa_id', $this->empresa_id))
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado))
            ->when($this->search, fn ($q) => $q->whereHas('equipo', fn ($q) =>
                $q->where('codigo_interno', 'like', "%{$this->search}%")
            ))
            ->latest('fecha_inicio')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.mantenimientos.mantenimientos-table');
    }
}
