<?php

namespace App\Livewire\Configuracion\Estados;

use App\Models\EstadoEquipo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class EstadosTable extends Component
{
    use WithPagination;

    public string $search            = '';
    public bool   $mostrar_inactivos = false;
    public int    $perPage           = 10;

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'mostrar_inactivos']);
        $this->resetPage();
    }

    #[On('estadoGuardado')]
    #[On('estadoEliminado')]
    public function refresh(): void {}

    // ── Stats ─────────────────────────────────────────────────────────────────

    #[Computed]
    public function total(): int
    {
        return EstadoEquipo::where('activo', true)->count();
    }

    #[Computed]
    public function conEquipos(): int
    {
        return EstadoEquipo::where('activo', true)->has('equipos')->count();
    }

    #[Computed]
    public function sinEquipos(): int
    {
        return EstadoEquipo::where('activo', true)->doesntHave('equipos')->count();
    }

    #[Computed]
    public function totalInactivos(): int
    {
        return EstadoEquipo::where('activo', false)->count();
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search, $this->mostrar_inactivos])->filter()->count();
    }

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
            ->when(! $this->mostrar_inactivos, fn($q) => $q->where('activo', true))
            ->when($this->search, fn($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->withCount('equipos')
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->paginate($this->perPage);

        return view('livewire.configuracion.estados.estados-table', [
            'estados' => $estados,
        ]);
    }
}