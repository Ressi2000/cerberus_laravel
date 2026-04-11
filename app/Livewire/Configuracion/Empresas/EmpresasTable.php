<?php
// ════════════════════════════════════════════════════════════════════════════
// app/Livewire/Configuracion/Empresas/EmpresasTable.php
// ════════════════════════════════════════════════════════════════════════════

namespace App\Livewire\Configuracion\Empresas;

use App\Models\Empresa;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class EmpresasTable extends Component
{
    use WithPagination;

    public string $search            = '';
    public bool   $mostrar_inactivas = false;
    public int    $perPage           = 10;

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'mostrar_inactivas']);
        $this->resetPage();
    }

    #[On('empresaGuardada')]
    #[On('empresaEliminada')]
    public function refresh(): void {}

    // ── Stats ─────────────────────────────────────────────────────────────────

    #[Computed]
    public function total(): int
    {
        return Empresa::where('activo', true)->count();
    }

    #[Computed]
    public function totalConUsuarios(): int
    {
        return Empresa::where('activo', true)
            ->whereHas('usuarios', fn($q) => $q->where('estado', 'Activo'))
            ->count();
    }

    #[Computed]
    public function totalConEquipos(): int
    {
        return Empresa::where('activo', true)
            ->whereHas('equipos', fn($q) => $q->where('activo', true))
            ->count();
    }

    #[Computed]
    public function totalInactivas(): int
    {
        return Empresa::where('activo', false)->count();
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search, $this->mostrar_inactivas])
            ->filter(fn($v) => $v !== '' && $v !== false)
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
            ->withCount([
                'usuarios'   => fn($q) => $q->where('estado', 'Activo'),
                'equipos'    => fn($q) => $q->where('activo', true),
                'ubicaciones' => fn($q) => $q->where('activo', true),
            ])
            ->when(! $this->mostrar_inactivas, fn($q) => $q->where('activo', true))
            ->when($this->search, fn($q) =>
                $q->where('nombre', 'like', "%{$this->search}%")
                  ->orWhere('rif',    'like', "%{$this->search}%")
            )
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->paginate($this->perPage);

        return view('livewire.configuracion.empresas.empresas-table', [
            'empresas' => $empresas,
        ]);
    }
}