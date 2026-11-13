<?php

namespace App\Livewire\MonitorRed;

use App\Models\Empresa;
use App\Models\MonitorCaida;
use App\Models\MonitorHost;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CaidasTable extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $host_id = '';

    #[Url(as: 'empresa')]
    public string $empresa_id = '';

    public string $desde    = '';
    public string $hasta    = '';
    public bool   $en_curso = false;
    public int    $perPage  = 25;

    public function mount(): void
    {
        $this->authorize('viewAny', MonitorHost::class);
    }

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'host_id', 'empresa_id', 'desde', 'hasta', 'en_curso']);
        $this->resetPage();
    }

    /**
     * Consulta filtrada del historial — compartida con la exportación a Excel
     * (MonitorRedController::exportarCaidas) para que el archivo coincida
     * exactamente con lo que se ve en pantalla.
     */
    public static function consulta(User $actor, array $f): Builder
    {
        $search = trim((string) ($f['search'] ?? ''));

        return MonitorCaida::query()
            ->visiblePara($actor)
            ->with(['host.empresa:id,nombre'])
            ->when(! empty($f['empresa_id']), fn ($q) => $q->whereHas('host', fn ($h) => $h->withTrashed()->where('empresa_id', $f['empresa_id'])))
            ->when(! empty($f['host_id']), fn ($q) => $q->where('monitor_host_id', (int) $f['host_id']))
            ->when($search !== '', fn ($q) => $q->whereHas('host', fn ($h) => $h->withTrashed()->where(function ($h) use ($search) {
                $h->where('nombre', 'like', "%{$search}%")
                  ->orWhere('host', 'like', "%{$search}%")
                  ->orWhere('proveedor', 'like', "%{$search}%");
            })))
            ->when(! empty($f['desde']), fn ($q) => $q->where('inicio', '>=', $f['desde'] . ' 00:00:00'))
            ->when(! empty($f['hasta']), fn ($q) => $q->where('inicio', '<=', $f['hasta'] . ' 23:59:59'))
            ->when(filter_var($f['en_curso'] ?? false, FILTER_VALIDATE_BOOLEAN), fn ($q) => $q->whereNull('fin'))
            ->orderByDesc('inicio');
    }

    private function filtros(): array
    {
        return [
            'search'     => $this->search,
            'host_id'    => $this->host_id,
            'empresa_id' => $this->empresa_id,
            'desde'      => $this->desde,
            'hasta'      => $this->hasta,
            'en_curso'   => $this->en_curso ? '1' : '',
        ];
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect($this->filtros())->filter()->count();
    }

    #[Computed]
    public function hostsOpciones()
    {
        return MonitorHost::visiblePara(Auth::user())->orderBy('nombre')
            ->get(['id', 'nombre', 'host'])
            ->mapWithKeys(fn ($h) => [$h->id => "{$h->nombre} ({$h->host})"]);
    }

    #[Computed]
    public function empresasOpciones()
    {
        return Auth::user()->hasRole('Administrador')
            ? Empresa::orderBy('nombre')->pluck('nombre', 'id')
            : collect();
    }

    #[Computed]
    public function stats(): array
    {
        $base = MonitorCaida::visiblePara(Auth::user());

        return [
            'en_curso' => (clone $base)->whereNull('fin')->count(),
            'd1'       => (clone $base)->where('inicio', '>', now()->subDay())->count(),
            'd30'      => (clone $base)->where('inicio', '>', now()->subDays(30))->count(),
            'promedio' => (int) (clone $base)->where('inicio', '>', now()->subDays(30))->whereNotNull('fin')->avg('duracion_segundos'),
        ];
    }

    public function render()
    {
        return view('livewire.monitor-red.caidas-table', [
            'caidas'      => self::consulta(Auth::user(), $this->filtros())->paginate($this->perPage),
            'exportarUrl' => route('admin.monitor-red.caidas.exportar', array_filter($this->filtros())),
        ]);
    }
}
