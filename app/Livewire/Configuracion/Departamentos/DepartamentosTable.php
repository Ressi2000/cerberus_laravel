<?php

namespace App\Livewire\Configuracion\Departamentos;

use App\Models\Departamento;
use App\Models\Empresa;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class DepartamentosTable extends Component
{
    use WithPagination;

    public string $search     = '';
    public string $empresa_id = '';
    public string $tipo       = ''; // '' | 'global' | 'empresa'
    public int    $perPage    = 10;

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'empresa_id', 'tipo']);
        $this->resetPage();
    }

    #[On('departamentoGuardado')]
    #[On('departamentoEliminado')]
    public function refresh(): void {}

    // ── Stats ─────────────────────────────────────────────────────────────────

    #[Computed]
    public function total(): int
    {
        return Departamento::count();
    }

    #[Computed]
    public function totalGlobales(): int
    {
        return Departamento::whereNull('empresa_id')->count();
    }

    #[Computed]
    public function totalPorEmpresa(): int
    {
        return Departamento::whereNotNull('empresa_id')->count();
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search, $this->empresa_id, $this->tipo])
            ->filter()
            ->count();
    }

    #[Computed]
    public function filterParams(): array
    {
        return array_filter([
            'search'     => $this->search     ?: null,
            'empresa_id' => $this->empresa_id ?: null,
            'tipo'       => $this->tipo        ?: null,
        ]);
    }

    public function render()
    {
        $departamentos = Departamento::query()
            ->with('empresa')
            ->withCount(['cargos', 'usuarios'])
            ->when($this->search, fn($q) =>
                $q->where('nombre', 'like', "%{$this->search}%")
                  ->orWhere('descripcion', 'like', "%{$this->search}%")
            )
            ->when($this->empresa_id, fn($q) =>
                $q->where('empresa_id', $this->empresa_id)
            )
            ->when($this->tipo === 'global', fn($q) =>
                $q->whereNull('empresa_id')
            )
            ->when($this->tipo === 'empresa', fn($q) =>
                $q->whereNotNull('empresa_id')
            )
            ->orderBy('nombre')
            ->paginate($this->perPage);

        return view('livewire.configuracion.departamentos.departamentos-table', [
            'departamentos' => $departamentos,
            'empresas'      => Empresa::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}