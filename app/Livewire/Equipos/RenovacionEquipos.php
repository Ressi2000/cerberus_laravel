<?php

namespace App\Livewire\Equipos;

use App\Models\CategoriaEquipo;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Services\RenovacionService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Renovación de equipos: cada equipo clasificado (obsoleto, repotenciable,
 * por revisar, sano) con el porqué, según los criterios de su categoría
 * (ver RenovacionService y CriterioRenovacion).
 */
class RenovacionEquipos extends Component
{
    use WithPagination;

    #[Url(as: 'empresa')]
    public string $empresa_id = '';
    #[Url(as: 'categoria')]
    public string $categoria_id = '';
    #[Url(as: 'clasificacion')]
    public string $clasificacion = '';
    public string $search = '';
    /** Solo los que tienen datos incompletos en su ficha. */
    public bool $incompletos = false;

    public function mount(): void
    {
        $actor = Auth::user();
        if (! $actor->hasRole('Administrador')) {
            $this->empresa_id = (string) ($actor->empresa_activa_id ?? '');
        }
    }

    public function updated(string $propiedad): void
    {
        if ($propiedad !== 'page') {
            $this->resetPage();
        }
    }

    #[On('criteriosRenovacionGuardados')]
    public function refrescar(): void
    {
        unset($this->evaluados);
    }

    public function verClasificacion(string $clasificacion): void
    {
        $this->clasificacion = $this->clasificacion === $clasificacion ? '' : $clasificacion;
        $this->resetPage();
    }

    #[Computed]
    public function empresasOpciones()
    {
        $actor = Auth::user();

        return $actor->hasRole('Administrador')
            ? Empresa::orderBy('nombre')->pluck('nombre', 'id')
            : Empresa::where('id', $actor->empresa_activa_id)->pluck('nombre', 'id');
    }

    #[Computed]
    public function categoriasOpciones()
    {
        return CategoriaEquipo::orderBy('nombre')->pluck('nombre', 'id');
    }

    /** Todos los equipos activos visibles, de categorías que se evalúan, ya clasificados. */
    #[Computed]
    public function evaluados(): Collection
    {
        $equipos = Equipo::visiblePara(Auth::user())
            ->where('activo', true)
            ->when($this->empresa_id, fn ($q) => $q->where('empresa_id', $this->empresa_id))
            ->when($this->categoria_id, fn ($q) => $q->where('categoria_id', $this->categoria_id))
            ->orderBy('codigo_interno')
            ->get();

        return app(RenovacionService::class)->evaluarEquipos($equipos)
            ->filter(fn ($r) => $r['criterio']->evaluar)
            ->values();
    }

    /** Cuántos hay de cada clasificación (con los filtros de empresa y categoría). */
    #[Computed]
    public function totales(): array
    {
        $c = $this->evaluados->countBy(fn ($r) => $r['resultado']['clasificacion']);

        return collect(RenovacionService::CLASIFICACIONES)->map(fn ($_, $k) => (int) ($c[$k] ?? 0))->all()
            + ['incompletos' => $this->evaluados->filter(fn ($r) => $r['resultado']['faltan'])->count()];
    }

    #[Computed]
    public function filtrados(): Collection
    {
        $q = mb_strtolower(trim($this->search));

        return $this->evaluados
            ->when($this->clasificacion, fn ($c) => $c->filter(fn ($r) => $r['resultado']['clasificacion'] === $this->clasificacion))
            ->when($this->incompletos, fn ($c) => $c->filter(fn ($r) => $r['resultado']['faltan']))
            ->when($q !== '', fn ($c) => $c->filter(fn ($r) => str_contains(mb_strtolower(
                $r['equipo']->codigo_interno . ' ' . ($r['resultado']['datos']['procesador_texto'] ?? '') . ' ' . ($r['resultado']['datos']['so'] ?? '')
            ), $q)))
            // Lo más urgente primero.
            ->sortBy(fn ($r) => array_search($r['resultado']['clasificacion'], array_keys(RenovacionService::CLASIFICACIONES)) . $r['equipo']->codigo_interno)
            ->values();
    }

    public function urlExportar(): string
    {
        return route('admin.renovacion.exportar', array_filter([
            'empresa' => $this->empresa_id, 'categoria' => $this->categoria_id,
            'clasificacion' => $this->clasificacion, 'incompletos' => $this->incompletos ? 1 : null,
        ]));
    }

    public function render()
    {
        $porPagina = 25;
        $pagina = $this->getPage();
        $items = $this->filtrados;

        return view('livewire.equipos.renovacion-equipos', [
            'resultados' => new LengthAwarePaginator($items->forPage($pagina, $porPagina), $items->count(), $porPagina, $pagina),
        ]);
    }
}
