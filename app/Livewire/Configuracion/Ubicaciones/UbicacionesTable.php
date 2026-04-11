<?php
// ════════════════════════════════════════════════════════════════════════════
// app/Livewire/Configuracion/Ubicaciones/UbicacionesTable.php
// ════════════════════════════════════════════════════════════════════════════

namespace App\Livewire\Configuracion\Ubicaciones;

use App\Models\Empresa;
use App\Models\Ubicacion;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class UbicacionesTable extends Component
{
    use WithPagination;

    public string $search            = '';
    public string $empresa_id        = '';
    public string $es_estado         = '';
    public bool   $mostrar_inactivas = false;
    public int    $perPage           = 10;

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'empresa_id', 'es_estado', 'mostrar_inactivas']);
        $this->resetPage();
    }

    #[On('ubicacionGuardada')]
    #[On('ubicacionEliminada')]
    public function refresh(): void {}

    // ── Stats ─────────────────────────────────────────────────────────────────

    #[Computed]
    public function total(): int
    {
        return Ubicacion::where('activo', true)->count();
    }

    #[Computed]
    public function totalForaneas(): int
    {
        return Ubicacion::where('activo', true)->where('es_estado', true)->count();
    }

    #[Computed]
    public function totalLocales(): int
    {
        return Ubicacion::where('activo', true)->where('es_estado', false)->count();
    }

    #[Computed]
    public function totalInactivas(): int
    {
        return Ubicacion::where('activo', false)->count();
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search, $this->empresa_id, $this->es_estado, $this->mostrar_inactivas])
            ->filter(fn($v) => $v !== '' && $v !== false)
            ->count();
    }

    #[Computed]
    public function filterParams(): array
    {
        return array_filter([
            'search'     => $this->search     ?: null,
            'empresa_id' => $this->empresa_id ?: null,
            'es_estado'  => $this->es_estado !== '' ? $this->es_estado : null,
        ]);
    }

    public function render()
    {
        $ubicaciones = Ubicacion::query()
            ->with('empresa')
            ->withCount([
                'usuarios' => fn($q) => $q->where('estado', 'Activo'),
                'equipos'  => fn($q) => $q->where('activo', true),
            ])
            ->when(! $this->mostrar_inactivas, fn($q) => $q->where('activo', true))
            ->when($this->search, fn($q) =>
                $q->where('nombre', 'like', "%{$this->search}%")
                  ->orWhere('descripcion', 'like', "%{$this->search}%")
            )
            ->when($this->empresa_id, fn($q) => $q->where('empresa_id', $this->empresa_id))
            ->when($this->es_estado !== '', fn($q) => $q->where('es_estado', (bool) $this->es_estado))
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->paginate($this->perPage);

        return view('livewire.configuracion.ubicaciones.ubicaciones-table', [
            'ubicaciones' => $ubicaciones,
            'empresas'    => Empresa::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}