<?php

namespace App\Livewire\Configuracion\Categorias;

use App\Models\CategoriaEquipo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CategoriasTable extends Component
{
    use WithPagination;
 
    public string $search  = '';
    public string $asignable = '';
    public int    $perPage = 10;
 
    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }
 
    public function resetFilters(): void
    {
        $this->reset(['search', 'asignable']);
        $this->resetPage();
    }
 
    #[On('categoriaGuardada')]
    #[On('categoriaEliminada')]
    public function refresh(): void {}
 
    // ── Stats ─────────────────────────────────────────────────────────────────
    #[Computed]
    public function total(): int
    {
        return CategoriaEquipo::count();
    }
 
    #[Computed]
    public function totalAsignables(): int
    {
        return CategoriaEquipo::where('asignable', true)->count();
    }
 
    #[Computed]
    public function totalConAtributos(): int
    {
        return CategoriaEquipo::has('atributos')->count();
    }
 
    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search, $this->asignable])->filter()->count();
    }

    // ── Parámetros de filtro para exportación ─────────────────────────────────
    // Misma interfaz que filterParams de UsuariosTable y EquiposTable
    #[Computed]
    public function filterParams(): array
    {
        return array_filter([
            'search'    => $this->search    ?: null,
            'asignable' => $this->asignable !== '' ? $this->asignable : null,
        ]);
    }
 
 
    public function render()
    {
        $categorias = CategoriaEquipo::query()
            ->when($this->search, fn($q) =>
                $q->where('nombre', 'like', "%{$this->search}%")
                  ->orWhere('descripcion', 'like', "%{$this->search}%")
            )
            ->when($this->asignable !== '', fn($q) =>
                $q->where('asignable', (bool) $this->asignable)
            )
            ->withCount('equipos')
            ->withCount('atributos')
            ->orderBy('nombre')
            ->paginate($this->perPage);
 
        return view('livewire.configuracion.categorias.categorias-table', [
            'categorias' => $categorias,
        ]);
    }
}
