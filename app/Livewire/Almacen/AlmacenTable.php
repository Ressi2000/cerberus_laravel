<?php

namespace App\Livewire\Almacen;

use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AlmacenTable extends Component
{
    use WithPagination;

    #[Url(as: 'empresa')]
    public string $empresa_id = '';

    public string $search            = '';
    public bool   $mostrar_inactivos = false;

    #[On('componenteGuardado')]
    #[On('stockActualizado')]
    #[On('componenteEliminado')]
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

    public function mount(): void
    {
        $actor = Auth::user();
        if (! $actor->hasRole('Administrador')) {
            $this->empresa_id = (string) ($actor->empresa_activa_id ?? '');
        }
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

    #[Computed]
    public function totalComponentes(): int
    {
        return ComponenteAlmacen::visiblePara(Auth::user())->activos()->count();
    }

    #[Computed]
    public function totalSinStock(): int
    {
        return ComponenteAlmacen::visiblePara(Auth::user())->activos()->where('stock_actual', 0)->count();
    }

    #[Computed]
    public function totalUnidadesEnStock(): int
    {
        return (int) ComponenteAlmacen::visiblePara(Auth::user())->activos()->sum('stock_actual');
    }

    public function desactivar(int $id): void
    {
        $componente = ComponenteAlmacen::findOrFail($id);
        $this->authorize('delete', $componente);
        $componente->update(['activo' => false]);
        $this->dispatch('toast', type: 'success', message: "«{$componente->nombre}» desactivado.");
    }

    public function reactivar(int $id): void
    {
        $componente = ComponenteAlmacen::findOrFail($id);
        $this->authorize('delete', $componente);
        $componente->update(['activo' => true]);
        $this->dispatch('toast', type: 'success', message: "«{$componente->nombre}» reactivado.");
    }

    #[Computed]
    public function componentes()
    {
        return ComponenteAlmacen::with('empresa')
            ->visiblePara(Auth::user())
            ->when(! $this->mostrar_inactivos, fn ($q) => $q->where('activo', true))
            ->when($this->empresa_id, fn ($q) => $q->where('empresa_id', $this->empresa_id))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->search}%")
                  ->orWhere('descripcion', 'like', "%{$this->search}%");
            }))
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.almacen.almacen-table');
    }
}
