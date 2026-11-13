<?php

namespace App\Livewire\MonitorRed;

use App\Livewire\MonitorRed\Concerns\DetectaCambiosDeEstado;
use App\Livewire\MonitorRed\Concerns\RevisaHosts;
use App\Models\MonitorCaida;
use App\Models\MonitorHost;
use App\Models\MonitorPruebaVelocidad;
use App\Services\MonitorRed\EstadisticasMonitor;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Dashboard del Monitor de red: KPIs, disponibilidad, latencia por hora,
 * distribución por tipo/proveedor, hosts más lentos y caídas recientes.
 * Se refresca solo con wire:poll (lee la BD; las revisiones las hace el scheduler).
 */
class MonitorRedDashboard extends Component
{
    use DetectaCambiosDeEstado, RevisaHosts;

    public function mount(): void
    {
        $this->authorize('viewAny', MonitorHost::class);
    }

    public function render(EstadisticasMonitor $stats)
    {
        $hosts   = $this->hostsBase()->orderBy('nombre')->get();
        $activos = $hosts->where('pausado', false);
        $this->detectarCambios($hosts);

        $up  = $activos->where('estado', MonitorHost::ESTADO_ACTIVO);
        $dn  = $activos->where('estado', MonitorHost::ESTADO_CAIDO);
        $lat = $up->pluck('latencia_ms')->filter(fn ($v) => $v !== null);

        $disp     = $stats->disponibilidad($activos);
        $promedio = function (string $k) use ($disp) {
            $v = collect($disp)->pluck($k)->filter(fn ($x) => $x !== null);
            return $v->isEmpty() ? null : round($v->avg(), 2);
        };

        $agrupar = fn (string $campo) => $activos
            ->groupBy(fn ($h) => $h->{$campo} ?: '(sin definir)')
            ->map(fn ($g) => [
                'n'  => $g->count(),
                'up' => $g->where('estado', MonitorHost::ESTADO_ACTIVO)->count(),
                'dn' => $g->where('estado', MonitorHost::ESTADO_CAIDO)->count(),
            ])
            ->sortByDesc('n');

        $hostIds      = $hosts->pluck('id');
        $caidasBase   = MonitorCaida::whereIn('monitor_host_id', $hostIds);

        return view('livewire.monitor-red.monitor-red-dashboard', [
            'total'       => $activos->count(),
            'pausados'    => $hosts->count() - $activos->count(),
            'up'          => $up->count(),
            'dn'          => $dn->count(),
            'pendientes'  => $activos->count() - $up->count() - $dn->count(),
            'latProm'     => $lat->isEmpty() ? null : $lat->avg(),
            'disp'        => ['d1' => $promedio('d1'), 'd7' => $promedio('d7'), 'd30' => $promedio('d30')],
            'caidasHoy'   => (clone $caidasBase)->where('inicio', '>', now()->subDay())->count(),
            'caidos'      => $dn->sortBy('estado_desde'),
            'porTipo'     => $agrupar('tipo'),
            'porProveedor'=> $agrupar('proveedor'),
            'horas'       => $stats->latenciaPorHora($activos->pluck('id')),
            'lentos'      => $up->whereNotNull('latencia_ms')->sortByDesc('latencia_ms')->take(5),
            'topCaidas'   => (clone $caidasBase)->where('inicio', '>', now()->subDays(30))
                ->selectRaw('monitor_host_id, COUNT(*) as n')->groupBy('monitor_host_id')
                ->orderByDesc('n')->limit(5)->with('host')->get(),
            'recientes'   => (clone $caidasBase)->with('host')->latest('inicio')->limit(6)->get(),
            'ultimoTest'  => MonitorPruebaVelocidad::visiblePara(Auth::user())
                ->when($this->empresa_id !== '', fn ($q) => $q->where('empresa_id', $this->empresa_id))
                ->latest()->first(),
            'intervalo'   => max(5, (int) config('monitor_red.intervalo_pantalla', 15)),
        ]);
    }
}
