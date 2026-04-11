<?php

namespace App\Livewire\Configuracion\Cargos;

use App\Models\Cargo;
use App\Models\Departamento;
use App\Models\Empresa;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CargosTable extends Component
{
    use WithPagination;

    public string $search          = '';
    public string $empresa_id      = '';
    public string $departamento_id = '';
    public int    $perPage         = 10;

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    /** Al cambiar empresa, limpiar departamento para evitar combinaciones inválidas */
    public function updatedEmpresaId(): void
    {
        $this->departamento_id = '';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'empresa_id', 'departamento_id']);
        $this->resetPage();
    }

    #[On('cargoGuardado')]
    #[On('cargoEliminado')]
    public function refresh(): void {}

    // ── Stats ─────────────────────────────────────────────────────────────────

    #[Computed]
    public function total(): int
    {
        return Cargo::count();
    }

    #[Computed]
    public function totalGlobales(): int
    {
        return Cargo::whereNull('empresa_id')->count();
    }

    #[Computed]
    public function totalPorEmpresa(): int
    {
        return Cargo::whereNotNull('empresa_id')->count();
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search, $this->empresa_id, $this->departamento_id])
            ->filter()
            ->count();
    }

    #[Computed]
    public function filterParams(): array
    {
        return array_filter([
            'search'          => $this->search          ?: null,
            'empresa_id'      => $this->empresa_id      ?: null,
            'departamento_id' => $this->departamento_id ?: null,
        ]);
    }

    /**
     * Departamentos disponibles en el filtro de la tabla.
     * Se filtra por empresa seleccionada + globales.
     */
    #[Computed]
    public function departamentosDisponibles()
    {
        return Departamento::where(function ($q) {
                $q->whereNull('empresa_id');
                if ($this->empresa_id) {
                    $q->orWhere('empresa_id', $this->empresa_id);
                }
            })
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    public function render()
    {
        $cargos = Cargo::query()
            ->with(['empresa', 'departamento'])
            ->withCount('usuarios')
            ->when($this->search, fn($q) =>
                $q->where('nombre', 'like', "%{$this->search}%")
            )
            ->when($this->empresa_id, fn($q) =>
                $q->where('empresa_id', $this->empresa_id)
            )
            ->when($this->departamento_id, fn($q) =>
                $q->where('departamento_id', $this->departamento_id)
            )
            ->orderBy('nombre')
            ->paginate($this->perPage);

        return view('livewire.configuracion.cargos.cargos-table', [
            'cargos'    => $cargos,
            'empresas'  => Empresa::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}