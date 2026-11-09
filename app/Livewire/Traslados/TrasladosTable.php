<?php

namespace App\Livewire\Traslados;

use App\Models\Traslado;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TrasladosTable extends Component
{
    use WithPagination;

    #[Url]
    public string $busqueda        = '';
    #[Url]
    public string $filtro_origen   = '';
    #[Url]
    public string $filtro_destino  = '';
    #[Url]
    public string $filtro_fecha_desde = '';
    #[Url]
    public string $filtro_fecha_hasta = '';
    #[Url]
    public int $perPage = 15;

    public function updatedBusqueda(): void    { $this->resetPage(); }
    public function updatedFiltroOrigen(): void { $this->resetPage(); }
    public function updatedFiltroDestino(): void { $this->resetPage(); }

    #[Computed]
    public function traslados()
    {
        $actor = Auth::user();

        return Traslado::with([
            'ubicacionOrigen',
            'ubicacionDestino',
            'recibe',
            'autoriza',
        ])
            ->withCount('items')
            ->visiblePara($actor)
            ->when($this->busqueda, function ($q) {
                $s = $this->busqueda;
                $q->where(function ($sq) use ($s) {
                    $sq->where('numero', 'like', "%{$s}%")
                        ->orWhere('motivo', 'like', "%{$s}%")
                        ->orWhereHas('recibe', fn($u) => $u->where('name', 'like', "%{$s}%"))
                        ->orWhereHas('ubicacionOrigen', fn($u) => $u->where('nombre', 'like', "%{$s}%"))
                        ->orWhereHas('ubicacionDestino', fn($u) => $u->where('nombre', 'like', "%{$s}%"));
                });
            })
            ->when($this->filtro_origen, fn($q) => $q->where('ubicacion_origen_id', $this->filtro_origen))
            ->when($this->filtro_destino, fn($q) => $q->where('ubicacion_destino_id', $this->filtro_destino))
            ->when($this->filtro_fecha_desde, fn($q) => $q->whereDate('fecha_traslado', '>=', $this->filtro_fecha_desde))
            ->when($this->filtro_fecha_hasta, fn($q) => $q->whereDate('fecha_traslado', '<=', $this->filtro_fecha_hasta))
            ->orderByDesc('fecha_traslado')
            ->orderByDesc('id')
            ->paginate($this->perPage);
    }

    /**
     * Ubicaciones para los filtros de Origen/Destino de la búsqueda.
     * Deliberadamente sin restricción por empresa: un traslado por
     * definición cruza ubicaciones, y el Analista necesita poder
     * buscar/filtrar traslados aunque el origen o destino no sea el
     * de su propia empresa (a diferencia de crear un traslado nuevo,
     * donde el origen sí se restringe a lo que puede gestionar).
     */
    #[Computed]
    public function ubicaciones()
    {
        return Ubicacion::where('activo', true)
            ->orderBy('es_estado')
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    #[Computed]
    public function ubicacionesOpciones(): array
    {
        return $this->ubicaciones->pluck('nombre', 'id')->toArray();
    }

    #[On('trasladoEliminado')]
    #[On('trasladoRevertido')]
    public function refrescar(): void
    {
        unset($this->traslados);
    }

    public function limpiarFiltros(): void
    {
        $this->busqueda          = '';
        $this->filtro_origen     = '';
        $this->filtro_destino    = '';
        $this->filtro_fecha_desde = '';
        $this->filtro_fecha_hasta = '';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.traslados.traslados-table');
    }
}
