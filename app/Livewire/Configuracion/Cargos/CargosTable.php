<?php
// ════════════════════════════════════════════════════════════════════════════
// app/Livewire/Configuracion/Cargos/CargosTable.php
// ════════════════════════════════════════════════════════════════════════════

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

    public string $search            = '';
    public string $empresa_id        = '';
    public string $departamento_id   = '';
    public bool   $mostrar_inactivos = false;
    public int    $perPage           = 10;

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function updatedEmpresaId(): void
    {
        $this->departamento_id = '';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'empresa_id', 'departamento_id', 'mostrar_inactivos']);
        $this->resetPage();
    }

    #[On('cargoGuardado')]
    #[On('cargoEliminado')]
    public function refresh(): void {}

    // ── Stats ─────────────────────────────────────────────────────────────────

    #[Computed]
    public function total(): int
    {
        return Cargo::where('activo', true)->count();
    }

    #[Computed]
    public function totalGlobales(): int
    {
        return Cargo::where('activo', true)->whereNull('empresa_id')->count();
    }

    #[Computed]
    public function totalPorEmpresa(): int
    {
        return Cargo::where('activo', true)->whereNotNull('empresa_id')->count();
    }

    #[Computed]
    public function totalInactivos(): int
    {
        return Cargo::where('activo', false)->count();
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search, $this->empresa_id, $this->departamento_id, $this->mostrar_inactivos])
            ->filter(fn($v) => $v !== '' && $v !== false)
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

    #[Computed]
    public function departamentosDisponibles()
    {
        return Departamento::where('activo', true)
            ->when($this->empresa_id, fn($q) =>
                $q->where(fn($q2) =>
                    $q2->where('empresa_id', $this->empresa_id)->orWhereNull('empresa_id')
                )
            )
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    public function render()
    {
        $cargos = Cargo::query()
            ->with(['empresa', 'departamento'])
            ->withCount(['usuarios' => fn($q) => $q->where('estado', 'Activo')])
            ->when(! $this->mostrar_inactivos, fn($q) => $q->where('activo', true))
            ->when($this->search, fn($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->when($this->empresa_id, fn($q) => $q->where('empresa_id', $this->empresa_id))
            ->when($this->departamento_id, fn($q) => $q->where('departamento_id', $this->departamento_id))
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->paginate($this->perPage);

        return view('livewire.configuracion.cargos.cargos-table', [
            'cargos'   => $cargos,
            'empresas' => Empresa::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}