<?php

namespace App\Services\MonitorRed;

use App\Models\MonitorCaida;
use App\Models\MonitorHost;
use App\Models\MonitorMedicion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cálculos de lectura para el dashboard y la tabla del Monitor de red.
 * Todo se calcula en bloque (pocas consultas) para el conjunto de hosts
 * visible, nunca host por host.
 */
class EstadisticasMonitor
{
    public const VENTANAS = ['d1' => 86400, 'd7' => 7 * 86400, 'd30' => 30 * 86400];

    /**
     * Disponibilidad (%) de cada host en 24 h / 7 d / 30 d, calculada a partir
     * de los intervalos de caída. La ventana empieza como muy pronto cuando el
     * host se dio de alta (un host de ayer no tiene "30 días al 100 %").
     *
     * @param  Collection<int, MonitorHost>  $hosts
     * @return array<int, array{d1: ?float, d7: ?float, d30: ?float}>
     */
    public function disponibilidad(Collection $hosts): array
    {
        if ($hosts->isEmpty()) {
            return [];
        }

        $ahora  = now()->getTimestamp();
        $caidas = MonitorCaida::whereIn('monitor_host_id', $hosts->pluck('id'))
            ->where(fn ($q) => $q->whereNull('fin')->orWhere('fin', '>', now()->subDays(30)))
            ->get(['monitor_host_id', 'inicio', 'fin'])
            ->groupBy('monitor_host_id');

        $resultado = [];

        foreach ($hosts as $host) {
            $creado = $host->created_at?->getTimestamp() ?? $ahora;

            foreach (self::VENTANAS as $clave => $segundos) {
                $inicio = max($ahora - $segundos, $creado);
                $total  = $ahora - $inicio;

                if ($total < 60) {
                    $resultado[$host->id][$clave] = null;
                    continue;
                }

                $caido = 0;
                foreach ($caidas->get($host->id, collect()) as $c) {
                    $a = max($c->inicio->getTimestamp(), $inicio);
                    $b = min($c->fin?->getTimestamp() ?? $ahora, $ahora);
                    if ($b > $a) {
                        $caido += $b - $a;
                    }
                }

                $resultado[$host->id][$clave] = round(100 * max(0, 1 - $caido / $total), 2);
            }
        }

        return $resultado;
    }

    /**
     * Latencia promedio y % de revisiones exitosas por hora, últimas 24 h,
     * con las 24 franjas siempre presentes (null = sin datos).
     *
     * @param  Collection<int, int>  $hostIds
     * @return list<array{hora: \Carbon\Carbon, ms: ?float, up: ?float}>
     */
    public function latenciaPorHora(Collection $hostIds): array
    {
        $desde = now()->subHours(23)->startOfHour();

        $expr = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d %H:00:00', medido_en)",
            'pgsql'  => "to_char(medido_en, 'YYYY-MM-DD HH24:00:00')",
            default  => "DATE_FORMAT(medido_en, '%Y-%m-%d %H:00:00')",
        };

        $filas = $hostIds->isEmpty() ? collect() : MonitorMedicion::query()
            ->whereIn('monitor_host_id', $hostIds)
            ->where('medido_en', '>=', $desde)
            ->selectRaw("{$expr} as hora, AVG(latencia_ms) as ms, AVG(CASE WHEN exito THEN 100.0 ELSE 0 END) as up")
            ->groupBy('hora')
            ->get()
            ->keyBy('hora');

        $franjas = [];
        for ($i = 0; $i < 24; $i++) {
            $hora = $desde->copy()->addHours($i);
            $fila = $filas->get($hora->format('Y-m-d H:00:00'));

            $franjas[] = [
                'hora' => $hora,
                'ms'   => $fila && $fila->ms !== null ? round((float) $fila->ms, 1) : null,
                'up'   => $fila ? round((float) $fila->up, 1) : null,
            ];
        }

        return $franjas;
    }

    /**
     * Últimas N mediciones de cada host para el sparkline:
     * latencia en ms, 0 si respondió sin latencia, null si falló.
     *
     * @param  Collection<int, int>  $hostIds
     * @return array<int, list<?float>>
     */
    public function series(Collection $hostIds, int $puntos = 30): array
    {
        if ($hostIds->isEmpty()) {
            return [];
        }

        return MonitorMedicion::whereIn('monitor_host_id', $hostIds)
            ->where('medido_en', '>=', now()->subMinutes($puntos + 10))
            ->orderBy('medido_en')
            ->get(['monitor_host_id', 'exito', 'latencia_ms'])
            ->groupBy('monitor_host_id')
            ->map(fn ($m) => $m->take(-$puntos)
                ->map(fn ($x) => $x->exito ? (float) ($x->latencia_ms ?? 0) : null)
                ->values()->all())
            ->all();
    }

    /** SVG inline del sparkline (líneas rojas = revisiones fallidas). */
    public static function sparkline(?array $valores): string
    {
        $w = 120;
        $h = 30;

        if (! $valores || count($valores) < 2) {
            return '<svg class="w-full h-8" viewBox="0 0 120 30"></svg>';
        }

        $nums  = array_filter($valores, fn ($v) => $v !== null);
        $max   = max(1, ...($nums ?: [1]));
        $paso  = $w / (count($valores) - 1);
        $d     = '';
        $marca = '';
        $corte = true;

        foreach ($valores as $i => $v) {
            $x = number_format($i * $paso, 1, '.', '');

            if ($v === null) {
                $marca .= "<line x1=\"{$x}\" x2=\"{$x}\" y1=\"0\" y2=\"{$h}\" stroke=\"#ef4444\" stroke-width=\"2\" opacity=\".7\"/>";
                $corte  = true;
                continue;
            }

            $y = number_format($h - 3 - ($v / $max) * ($h - 8), 1, '.', '');
            $d .= ($corte ? 'M' : 'L') . "{$x} {$y} ";
            $corte = false;
        }

        return "<svg class=\"w-full h-8\" viewBox=\"0 0 {$w} {$h}\" preserveAspectRatio=\"none\">"
            . "<path d=\"{$d}\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.5\" vector-effect=\"non-scaling-stroke\"/>{$marca}</svg>";
    }
}
