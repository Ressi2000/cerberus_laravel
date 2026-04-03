<?php

namespace App\Livewire\Configuracion\Estados;

use App\Models\EstadoEquipo;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class EstadosTable extends Component
{
    use WithPagination;

    public string $search  = '';
    public int    $perPage = 10;

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search']);
        $this->resetPage();
    }

    #[On('estadoGuardado')]
    #[On('estadoEliminado')]
    public function refresh(): void {}

    #[Computed] public function total(): int
    {
        return EstadoEquipo::count();
    }
    #[Computed] public function conEquipos(): int
    {
        return EstadoEquipo::has('equipos')->count();
    }
    #[Computed] public function sinEquipos(): int
    {
        return EstadoEquipo::doesntHave('equipos')->count();
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search])->filter()->count();
    }

    // ── Parámetros de filtro para exportación ─────────────────────────────────
    #[Computed]
    public function filterParams(): array
    {
        return array_filter([
            'search' => $this->search ?: null,
        ]);
    }

    public function render()
    {
        $estados = EstadoEquipo::query()
            ->when($this->search, fn($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->withCount('equipos')
            ->orderBy('nombre')
            ->paginate($this->perPage);

        return view('livewire.configuracion.estados.estados-table', [
            'estados' => $estados,
        ]);
    }
}
