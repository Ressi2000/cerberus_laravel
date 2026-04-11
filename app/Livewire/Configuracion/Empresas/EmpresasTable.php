<?php

namespace App\Livewire\Configuracion\Empresas;

use App\Models\Empresa;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class EmpresasTable extends Component
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

    #[On('empresaGuardada')]
    #[On('empresaEliminada')]
    public function refresh(): void {}

    // ── Stats ─────────────────────────────────────────────────────────────────

    #[Computed]
    public function total(): int
    {
        return Empresa::count();
    }

    #[Computed]
    public function totalEliminadas(): int
    {
        return Empresa::onlyTrashed()->count();
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search])
            ->filter(fn($v) => $v !== '')
            ->count();
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
        $empresas = Empresa::query()
            ->withCount(['usuarios', 'equipos', 'ubicaciones'])
            ->when($this->search, fn($q) =>
                $q->where('nombre', 'like', "%{$this->search}%")
                  ->orWhere('rif', 'like', "%{$this->search}%")
            )
            ->orderBy('nombre')
            ->paginate($this->perPage);

        return view('livewire.configuracion.empresas.empresas-table', [
            'empresas' => $empresas,
        ]);
    }
}