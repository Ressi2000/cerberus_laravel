<?php

namespace App\Livewire\Configuracion\LicenciasMicrosoft;

use App\Models\TipoLicenciaMicrosoft;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class LicenciasMicrosoftTable extends Component
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

    #[On('licenciaMicrosoftGuardada')]
    #[On('licenciaMicrosoftEliminada')]
    public function refresh(): void {}

    // ── Stats ─────────────────────────────────────────────────────────────────

    #[Computed]
    public function total(): int
    {
        return TipoLicenciaMicrosoft::where('activo', true)->count();
    }

    #[Computed]
    public function conUsuarios(): int
    {
        return TipoLicenciaMicrosoft::where('activo', true)->has('usuarios')->count();
    }

    #[Computed]
    public function sinUsuarios(): int
    {
        return TipoLicenciaMicrosoft::where('activo', true)->doesntHave('usuarios')->count();
    }

    #[Computed]
    public function totalInactivos(): int
    {
        return TipoLicenciaMicrosoft::where('activo', false)->count();
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
        $tipos = TipoLicenciaMicrosoft::query()
            ->when(! $this->mostrar_inactivos, fn($q) => $q->where('activo', true))
            ->when($this->search, fn($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->withCount('usuarios')
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->paginate($this->perPage);

        return view('livewire.configuracion.licencias-microsoft.licencias-microsoft-table', [
            'tipos' => $tipos,
        ]);
    }
}
