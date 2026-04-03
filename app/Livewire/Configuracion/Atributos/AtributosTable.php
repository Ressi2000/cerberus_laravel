<?php

namespace App\Livewire\Configuracion\Atributos;

use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class AtributosTable extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $categoria_id = '';
    public string $tipo         = '';
    public int    $perPage      = 15;

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'categoria_id', 'tipo']);
        $this->resetPage();
    }

    #[On('atributoGuardado')]
    #[On('atributoEliminado')]
    public function refresh(): void {}

    #[Computed] public function total(): int
    {
        return AtributoEquipo::count();
    }
    #[Computed] public function requeridos(): int
    {
        return AtributoEquipo::where('requerido', true)->count();
    }
    #[Computed] public function filtrables(): int
    {
        return AtributoEquipo::where('filtrable', true)->count();
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search, $this->categoria_id, $this->tipo])->filter()->count();
    }

    // ── Parámetros de filtro para exportación ─────────────────────────────────
    #[Computed]
    public function filterParams(): array
    {
        return array_filter([
            'search'       => $this->search       ?: null,
            'categoria_id' => $this->categoria_id ?: null,
            'tipo'         => $this->tipo         ?: null,
        ]);
    }

    public function render()
    {
        $atributos = AtributoEquipo::query()
            ->with('categoria')
            ->when($this->search, fn($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->when($this->categoria_id, fn($q) => $q->where('categoria_id', $this->categoria_id))
            ->when($this->tipo, fn($q) => $q->where('tipo', $this->tipo))
            ->orderBy('categoria_id')
            ->orderBy('orden')
            ->paginate($this->perPage);
 
        return view('livewire.configuracion.atributos.atributos-table', [
            'atributos'  => $atributos,
            'categorias' => CategoriaEquipo::orderBy('nombre')->pluck('nombre', 'id'),
            'tipos'      => [
                'string'  => 'Texto',
                'text'    => 'Texto largo',
                'integer' => 'Entero',
                'decimal' => 'Decimal',
                'boolean' => 'Sí / No',
                'date'    => 'Fecha',
                'select'  => 'Lista',
            ],
        ]);
    }
}
