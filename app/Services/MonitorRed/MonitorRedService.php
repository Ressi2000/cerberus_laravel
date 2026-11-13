<?php

namespace App\Services\MonitorRed;

use App\Models\MonitorHost;
use App\Models\MonitorMedicion;
use App\Models\User;
use App\Notifications\MonitorHostEstadoNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * MonitorRedService
 *
 * Revisa un conjunto de hosts (ping en paralelo → TCP de respaldo para los
 * que fallan), guarda el resultado en cada MonitorHost y notifica caídas y
 * recuperaciones a los analistas de la empresa y a los administradores.
 *
 * Una sola revisión a la vez (cache lock): el scheduler y el botón
 * "Revisar ahora" no se pisan ni duplican caídas.
 */
class MonitorRedService
{
    private const LOCK = 'monitor-red:revision';

    public function __construct(private SondaRed $sonda) {}

    /**
     * @param  Collection<int, MonitorHost>  $hosts
     * @return array{revisados: int, caidas: int, recuperados: int}|null  null si ya hay otra revisión en curso
     */
    public function revisar(Collection $hosts): ?array
    {
        $timeout = max(1, (int) config('monitor_red.timeout', 2));
        $lock    = Cache::lock(self::LOCK, 600);

        if (! $lock->get()) {
            return null;
        }

        try {
            return $this->ejecutar($hosts->reject(fn (MonitorHost $h) => $h->pausado)->values(), $timeout);
        } finally {
            $lock->release();
        }
    }

    /** Revisa todos los hosts no pausados (lo llama el scheduler). */
    public function revisarTodos(): ?array
    {
        return $this->revisar(MonitorHost::monitoreables()->get());
    }

    /** Borra mediciones más antiguas que la retención configurada. */
    public function purgarMediciones(): int
    {
        $dias = max(1, (int) config('monitor_red.retencion_mediciones_dias', 15));

        return MonitorMedicion::where('medido_en', '<', now()->subDays($dias))->delete();
    }

    private function ejecutar(Collection $hosts, int $timeout): array
    {
        $resumen = ['revisados' => 0, 'caidas' => 0, 'recuperados' => 0];

        if ($hosts->isEmpty()) {
            return $resumen;
        }

        $validos = $hosts->filter(fn (MonitorHost $h) => MonitorHost::hostValido($h->host));

        // 1) Ping a todos en paralelo
        $ping = $this->sonda->ping(
            $validos->mapWithKeys(fn (MonitorHost $h) => [$h->id => $h->host])->all(),
            $timeout,
            max(1, (int) config('monitor_red.concurrencia', 20))
        );

        // 2) TCP de respaldo solo para los que fallaron el ping y tienen puerto
        $tcp = $this->sonda->tcp(
            $validos->filter(fn (MonitorHost $h) => ! ($ping[$h->id][0] ?? false) && $h->puerto_tcp)
                ->mapWithKeys(fn (MonitorHost $h) => [$h->id => [$h->host, $h->puerto_tcp]])
                ->all(),
            $timeout
        );

        $ahora  = now();
        $umbral = (int) config('monitor_red.fallos_para_caida', 2);

        foreach ($hosts as $host) {
            [$ok, $ms] = $ping[$host->id] ?? [false, null];
            $metodo    = 'ping';

            if (! $ok && $host->puerto_tcp) {
                [$ok, $ms] = $tcp[$host->id] ?? [false, null];
                $metodo    = $ok ? 'TCP:' . $host->puerto_tcp : 'ping/TCP';
            }

            try {
                $evento = $host->registrarResultado($ok, $ms, $metodo, $ahora, $umbral);
            } catch (Throwable $e) {
                Log::error("MonitorRed: error guardando resultado del host #{$host->id}: {$e->getMessage()}");
                continue;
            }

            $resumen['revisados']++;

            if ($evento === MonitorHost::EVENTO_CAIDA) {
                $resumen['caidas']++;
                $this->notificar($host, false);
            } elseif ($evento === MonitorHost::EVENTO_RECUPERADO) {
                $resumen['recuperados']++;
                $this->notificar($host, true);
            }
        }

        return $resumen;
    }

    private function notificar(MonitorHost $host, bool $recuperado): void
    {
        if (! config('monitor_red.notificar', true)) {
            return;
        }

        try {
            $destinatarios = User::role('Analista')
                ->where('empresa_activa_id', $host->empresa_id)
                ->get()
                ->merge(User::role('Administrador')->get())
                ->unique('id');

            $duracion = $recuperado
                ? $host->caidas()->whereNotNull('fin')->latest('fin')->value('duracion_segundos')
                : null;

            foreach ($destinatarios as $user) {
                $user->notify(new MonitorHostEstadoNotification($host, $recuperado, $duracion));
            }
        } catch (Throwable $e) {
            Log::error("MonitorRed: error notificando el host #{$host->id}: {$e->getMessage()}");
        }
    }
}
