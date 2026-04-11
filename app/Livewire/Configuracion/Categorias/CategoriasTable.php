<?php

namespace App\Livewire\Configuracion\Categorias;

use App\Models\CategoriaEquipo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * CategoriasTable — v2
 *
 * Cambios respecto a v1:
 *  - Nuevo filtro `mostrar_inactivas` (toggle) para ver categorías desactivadas.
 *  - Stats: agrega card "Inactivas".
 *  - Filas inactivas muestran badge "Inactiva" y botón "Reactivar".
 *  - Las filas activas muestran el botón "Desactivar" (ícono block) en vez de "Eliminar".
 *  - Selects de filtros solo muestran activas por defecto.
 */
class CategoriasTable extends Component
{
    use WithPagination;

    public string $search            = '';
    public string $asignable         = '';
    public bool   $mostrar_inactivas = false;

    // ── Eventos que refrescan la tabla ────────────────────────────────────────

    #[On('categoriaGuardada')]
    #[On('categoriaEliminada')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    // ── Filtros ───────────────────────────────────────────────────────────────

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'asignable', 'mostrar_inactivas']);
        $this->resetPage();
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    #[Computed]
    public function total(): int
    {
        return CategoriaEquipo::where('activo', true)->count();
    }

    #[Computed]
    public function totalAsignables(): int
    {
        return CategoriaEquipo::where('activo', true)->where('asignable', true)->count();
    }

    #[Computed]
    public function totalConAtributos(): int
    {
        return CategoriaEquipo::where('activo', true)->has('atributos')->count();
    }

    #[Computed]
    public function totalInactivas(): int
    {
        return CategoriaEquipo::where('activo', false)->count();
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([
            $this->search !== '',
            $this->asignable !== '',
            $this->mostrar_inactivas,
        ])->filter()->count();
    }

    // ── Query principal ───────────────────────────────────────────────────────

    #[Computed]
    public function categorias()
    {
        return CategoriaEquipo::withCount([
            'equipos'  => fn($q) => $q->where('activo', true),
            'atributos',
        ])
        ->when(! $this->mostrar_inactivas, fn($q) => $q->where('activo', true))
        ->when($this->search, fn($q) =>
            $q->where(function ($q) {
                $q->where('nombre',      'like', "%{$this->search}%")
                  ->orWhere('descripcion', 'like', "%{$this->search}%");
            })
        )
        ->when($this->asignable !== '', fn($q) =>
            $q->where('asignable', (bool) $this->asignable)
        )
        ->orderByDesc('activo')   // activas primero
        ->orderBy('nombre')
        ->paginate(15);
    }

    // ── Params para exportación ───────────────────────────────────────────────

    #[Computed]
    public function filterParams(): array
    {
        return array_filter([
            'search'   => $this->search,
            'asignable' => $this->asignable,
        ]);
    }

    public function render()
    {
        return view('livewire.configuracion.categorias.categorias-table');
    }
}