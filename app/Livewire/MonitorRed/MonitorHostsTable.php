<?php

namespace App\Livewire\MonitorRed;

use App\Livewire\MonitorRed\Concerns\DetectaCambiosDeEstado;
use App\Livewire\MonitorRed\Concerns\RevisaHosts;
use App\Models\MonitorHost;
use App\Services\MonitorRed\EstadisticasMonitor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MonitorHostsTable extends Component
{
    use WithPagination, DetectaCambiosDeEstado, RevisaHosts;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $estado = '';      // Activo | Caido | Pendiente | Pausado

    #[Url]
    public string $tipo = '';

    #[Url]
    public string $proveedor = '';

    #[Url]
    public string $orden = 'estado'; // estado | nombre | host | tipo | proveedor | latencia_ms

    #[Url]
    public string $dir = 'asc';

    #[Url]
    public string $vista = 'lista';  // lista | cuadros

    public int $perPage = 50;

    /** @var list<int> */
    public array $seleccion = [];

    private const ORDENABLES = ['estado', 'nombre', 'host', 'tipo', 'proveedor', 'latencia_ms'];

    public function mount(): void
    {
        $this->authorize('viewAny', MonitorHost::class);
    }

    #[On('monitorHostGuardado')]
    #[On('monitorHostsImportados')]
    public function refrescar(): void
    {
        unset($this->tiposOpciones, $this->proveedoresOpciones);
    }

    public function updated(string $property): void
    {
        if (! in_array($property, ['page', 'seleccion', 'vista'], true) && ! str_starts_with($property, 'seleccion.')) {
            $this->resetPage();
        }
    }

    public function ordenar(string $campo): void
    {
        if (! in_array($campo, self::ORDENABLES, true)) {
            return;
        }

        $this->dir   = $this->orden === $campo && $this->dir === 'asc' ? 'desc' : 'asc';
        $this->orden = $campo;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'estado', 'tipo', 'proveedor', 'empresa_id']);
        $this->resetPage();
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search, $this->estado, $this->tipo, $this->proveedor, $this->empresa_id])->filter()->count();
    }

    #[Computed]
    public function tiposOpciones()
    {
        return MonitorHost::visiblePara(Auth::user())->distinct()->orderBy('tipo')->pluck('tipo', 'tipo');
    }

    #[Computed]
    public function proveedoresOpciones()
    {
        return MonitorHost::visiblePara(Auth::user())->whereNotNull('proveedor')->where('proveedor', '!=', '')
            ->distinct()->orderBy('proveedor')->pluck('proveedor', 'proveedor');
    }

    #[Computed]
    public function stats(): array
    {
        $base = $this->hostsBase();

        return [
            'total'    => (clone $base)->count(),
            'activos'  => (clone $base)->monitoreables()->where('estado', MonitorHost::ESTADO_ACTIVO)->count(),
            'caidos'   => (clone $base)->caidos()->count(),
            'pausados' => (clone $base)->where('pausado', true)->count(),
        ];
    }

    // ── Acciones ────────────────────────────────────────────────────────────

    public function alternarPausa(int $id): void
    {
        $host = MonitorHost::findOrFail($id);
        $this->authorize('update', $host);

        $host->update(['pausado' => ! $host->pausado]);

        $this->dispatch('toast', type: 'success', message: $host->pausado
            ? "Monitoreo de «{$host->nombre}» pausado."
            : "Monitoreo de «{$host->nombre}» reanudado.");
    }

    #[On('monitorHostEliminarConfirmado')]
    public function eliminar(array $ids): void
    {
        $hosts = MonitorHost::whereIn('id', array_map('intval', $ids))->get();

        if ($hosts->isEmpty()) {
            return;
        }

        foreach ($hosts as $host) {
            $this->authorize('delete', $host);
        }

        DB::transaction(function () use ($hosts) {
            foreach ($hosts as $host) {
                $host->reiniciarEstado(); // cierra la caída abierta
                $host->delete();
            }
        });

        $this->seleccion = array_values(array_diff($this->seleccion, $hosts->pluck('id')->all()));
        $this->dispatch('toast', type: 'success', message: $hosts->count() === 1
            ? "«{$hosts->first()->nombre}» quitado del monitoreo."
            : "{$hosts->count()} hosts quitados del monitoreo.");
    }

    public function seleccionarPagina(bool $marcar, array $ids): void
    {
        $ids = array_map('intval', $ids);

        $this->seleccion = $marcar
            ? array_values(array_unique([...$this->seleccion, ...$ids]))
            : array_values(array_diff($this->seleccion, $ids));
    }

    // ── Render ──────────────────────────────────────────────────────────────

    public function render(EstadisticasMonitor $estadisticas)
    {
        $dir   = $this->dir === 'desc' ? 'desc' : 'asc';
        $orden = in_array($this->orden, self::ORDENABLES, true) ? $this->orden : 'estado';

        $query = $this->hostsBase()
            ->with(['empresa:id,nombre', 'equipo:id,codigo_interno'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->search}%")
                  ->orWhere('host', 'like', "%{$this->search}%")
                  ->orWhere('tipo', 'like', "%{$this->search}%")
                  ->orWhere('proveedor', 'like', "%{$this->search}%");
            }))
            ->when($this->tipo, fn ($q) => $q->where('tipo', $this->tipo))
            ->when($this->proveedor, fn ($q) => $q->where('proveedor', $this->proveedor))
            ->when($this->estado === 'Pausado', fn ($q) => $q->where('pausado', true))
            ->when(in_array($this->estado, [MonitorHost::ESTADO_ACTIVO, MonitorHost::ESTADO_CAIDO, MonitorHost::ESTADO_PENDIENTE], true),
                fn ($q) => $q->where('pausado', false)->where('estado', $this->estado));

        if ($orden === 'estado') {
            // Caídos primero, luego pendientes, activos y al final los pausados.
            $query->orderByRaw(
                "CASE WHEN pausado = 1 THEN 3 WHEN estado = 'Caido' THEN 0 WHEN estado = 'Pendiente' THEN 1 ELSE 2 END {$dir}"
            );
        } elseif ($orden === 'latencia_ms') {
            $query->orderByRaw("CASE WHEN latencia_ms IS NULL THEN 1 ELSE 0 END")->orderBy('latencia_ms', $dir);
        } else {
            $query->orderBy($orden, $dir);
        }

        $hosts = $query->orderBy('nombre')->paginate($this->perPage);

        $this->detectarCambios($hosts->getCollection());

        return view('livewire.monitor-red.monitor-hosts-table', [
            'hosts'     => $hosts,
            'disp'      => $estadisticas->disponibilidad($hosts->getCollection()),
            'series'    => $this->vista === 'cuadros' ? $estadisticas->series($hosts->getCollection()->pluck('id')) : [],
            'intervalo' => max(5, (int) config('monitor_red.intervalo_pantalla', 15)),
        ]);
    }
}
