<?php

namespace App\Services\MonitorRed;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * SondaRed
 *
 * Hace las pruebas de red de bajo nivel, en PARALELO:
 *   - ping(): lanza un proceso `ping` por host a la vez (en bloques de
 *     `concurrencia`), así 100 hosts tardan ~timeout segundos y no
 *     100 × timeout como en una revisión secuencial.
 *   - tcp(): abre todas las conexiones TCP de respaldo de forma asíncrona y
 *     espera con stream_select().
 *
 * El comando se pasa como arreglo (sin shell), así que no hay inyección de
 * comandos aunque el host venga de la base de datos; además los hosts se
 * validan con MonitorHost::hostValido() antes de llegar aquí.
 *
 * Requiere `proc_open` habilitado en PHP y el binario `ping` en el servidor.
 */
class SondaRed
{
    /**
     * @param  array<int|string, string>  $hosts  clave => host
     * @return array<int|string, array{0: bool, 1: ?float}>  clave => [ok, latencia_ms]
     */
    public function ping(array $hosts, int $timeout, int $concurrencia = 20): array
    {
        $resultados = [];

        foreach (array_chunk($hosts, max(1, $concurrencia), true) as $bloque) {
            $procesos = [];

            foreach ($bloque as $clave => $host) {
                try {
                    $p = new Process($this->comandoPing($host, $timeout));
                    $p->setTimeout($timeout + 5); // margen para la resolución DNS
                    $p->start();
                    $procesos[$clave] = $p;
                } catch (Throwable $e) {
                    Log::warning("MonitorRed: no se pudo ejecutar ping ({$e->getMessage()}). ¿proc_open deshabilitado?");
                    $resultados[$clave] = [false, null];
                }
            }

            foreach ($procesos as $clave => $p) {
                try {
                    $p->wait();
                } catch (ProcessTimedOutException) {
                    $p->stop(0);
                    $resultados[$clave] = [false, null];
                    continue;
                }

                $resultados[$clave] = $this->interpretarPing($p->getExitCode(), $p->getOutput());
            }
        }

        return $resultados;
    }

    /**
     * @param  array<int|string, array{0: string, 1: int}>  $objetivos  clave => [host, puerto]
     * @return array<int|string, array{0: bool, 1: ?float}>
     */
    public function tcp(array $objetivos, int $timeout): array
    {
        $resultados = [];
        $sockets    = [];
        $inicios    = [];

        foreach ($objetivos as $clave => [$host, $puerto]) {
            $destino = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? "[{$host}]" : $host;
            $inicio  = microtime(true);

            $s = @stream_socket_client(
                "tcp://{$destino}:{$puerto}",
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
            );

            if ($s === false) {
                $resultados[$clave] = [false, null];
                continue;
            }

            stream_set_blocking($s, false);
            $sockets[$clave] = $s;
            $inicios[$clave] = $inicio;
        }

        $limite = microtime(true) + $timeout;

        while ($sockets && ($restante = $limite - microtime(true)) > 0) {
            $escritura = array_values($sockets);
            $lectura   = null;
            $excepcion = null;

            $listos = @stream_select($lectura, $escritura, $excepcion, (int) $restante, (int) (fmod($restante, 1) * 1e6));

            if (! $listos) {
                break; // timeout o error de select
            }

            foreach ($escritura as $s) {
                $clave = array_search($s, $sockets, true);

                // Un socket queda "escribible" tanto al conectar como al fallar:
                // solo tiene dirección remota si la conexión se estableció.
                $ok = @stream_socket_get_name($s, true) !== false;

                $resultados[$clave] = [$ok, $ok ? round((microtime(true) - $inicios[$clave]) * 1000, 1) : null];

                fclose($s);
                unset($sockets[$clave]);
            }
        }

        foreach ($sockets as $clave => $s) {
            fclose($s);
            $resultados[$clave] = [false, null];
        }

        return $resultados;
    }

    /** @return list<string> */
    protected function comandoPing(string $host, int $timeout): array
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => ['ping', '-n', '1', '-w', (string) ($timeout * 1000), $host],
            'Darwin'  => ['ping', '-n', '-c', '1', '-t', (string) $timeout, $host],
            default   => ['ping', '-n', '-c', '1', '-W', (string) $timeout, $host],
        };
    }

    /** @return array{0: bool, 1: ?float} */
    protected function interpretarPing(?int $codigo, string $salida): array
    {
        // Windows devuelve 0 aun con "Host de destino inaccesible": se revisa el texto.
        $ok = $codigo === 0 && ! preg_match('/unreachable|inaccesible|TTL expired|tiempo de espera agotado/i', $salida);

        $ms = preg_match('/(?:time|tiempo)[=<]\s*([\d.,]+)\s*ms/i', $salida, $m)
            ? (float) str_replace(',', '.', $m[1])
            : null;

        return [$ok, $ok ? $ms : null];
    }
}
