<?php

namespace App\Asistente\Proveedores;

use App\Asistente\AsistenteException;
use App\Asistente\Herramientas;
use App\Asistente\Privacidad;
use App\Asistente\Respuesta;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gemini (API de Google AI Studio) con "function calling": Gemini pide una
 * herramienta, Cerberus la ejecuta con los permisos del usuario y le
 * devuelve el resultado, hasta que Gemini tiene la respuesta.
 *
 * Gemini nunca ve la base de datos: solo lo que devuelven las herramientas.
 */
class Gemini implements ProveedorIA
{
    public function __construct(private Herramientas $herramientas)
    {
    }

    public function responder(User $actor, string $pregunta, array $historial, string $instrucciones, Privacidad $privacidad): Respuesta
    {
        $config = config('asistente.gemini');
        if (empty($config['api_key'])) {
            throw new AsistenteException('El asistente todavía no está configurado. Avísale al administrador.', 'Falta GEMINI_API_KEY en el .env.');
        }

        $this->modeloUsado = null;
        $this->sinRazonamiento = false;
        $this->fin = microtime(true) + (int) config('asistente.tiempo_maximo', 75);
        $contenidos = $historial;
        $contenidos[] = ['role' => 'user', 'parts' => [['text' => $pregunta]]];
        $usadas = [];

        for ($paso = 0; $paso < config('asistente.max_pasos', 8); $paso++) {
            $respuesta = $this->llamar($config, $contenidos, $instrucciones);   // controla el tiempo que queda
            $contenido = $respuesta['candidates'][0]['content'] ?? null;

            if (! $contenido || empty($contenido['parts'])) {
                $motivo = $respuesta['candidates'][0]['finishReason'] ?? $respuesta['promptFeedback']['blockReason'] ?? 'desconocido';
                throw new AsistenteException('No pude responder esa pregunta. Intenta reformularla.', "Gemini no devolvió respuesta (motivo: {$motivo}).");
            }

            // Se guarda tal cual (incluye las "firmas de pensamiento" que Gemini exige de vuelta).
            $contenido['role'] = 'model';
            $contenidos[] = $contenido;

            $llamadas = array_values(array_filter($contenido['parts'], fn ($p) => isset($p['functionCall'])));

            if (! $llamadas) {
                $texto = collect($contenido['parts'])
                    ->filter(fn ($p) => isset($p['text']) && empty($p['thought']))
                    ->pluck('text')->implode('');

                return $this->terminar($texto, $usadas, $contenidos, $this->modeloUsado ?? $config['modelo']);
            }

            $partes = [];
            foreach ($llamadas as $parte) {
                $nombre = $parte['functionCall']['name'] ?? '';
                // La IA pide con códigos ("Persona-12"); la consulta se hace con el nombre real…
                $args   = $privacidad->revelar((array) ($parte['functionCall']['args'] ?? []));
                $resultado = $this->herramientas->ejecutar($nombre, $actor, $args);

                // "_adjunto" (enlace de un informe) y "_grafico" son para la pantalla, no para la IA.
                $adjunto = $resultado['datos']['_adjunto'] ?? null;
                $grafico = $resultado['datos']['_grafico'] ?? null;
                unset($resultado['datos']['_adjunto'], $resultado['datos']['_grafico']);
                $usadas[] = array_filter(['nombre' => $nombre, 'args' => $args, 'ok' => $resultado['ok'], 'adjunto' => $adjunto, 'grafico' => $grafico], fn ($v) => $v !== null);

                $partes[] = ['functionResponse' => array_filter([
                    'id'       => $parte['functionCall']['id'] ?? null,
                    'name'     => $nombre,
                    // …y lo que vuelve a la IA lleva los nombres cambiados por códigos.
                    'response' => $privacidad->ocultar($resultado),
                ], fn ($v) => $v !== null)];
            }
            $contenidos[] = ['role' => 'user', 'parts' => $partes];
        }

        return $this->terminar(
            'Necesité demasiadas consultas para esta pregunta y me detuve. ¿Puedes hacerla más concreta? [[SIN_DATOS]]',
            $usadas, $contenidos, $this->modeloUsado ?? $config['modelo']
        );
    }

    /** Declaraciones de las herramientas en el formato de Gemini (tipos en mayúsculas). */
    public function declaraciones(): array
    {
        return array_map(function (array $d) {
            $props = [];
            foreach ((array) $d['input_schema']['properties'] as $nombre => $p) {
                $props[$nombre] = array_filter([
                    'type'        => strtoupper($p['type'] ?? 'string'),
                    'description' => $p['description'] ?? null,
                    'enum'        => $p['enum'] ?? null,
                ]);
            }

            return [
                'name'        => $d['name'],
                'description' => $d['description'],
                'parameters'  => array_filter([
                    'type'       => 'OBJECT',
                    'properties' => $props,
                    'required'   => $d['input_schema']['required'] ?: null,
                ]),
            ];
        }, $this->herramientas->definiciones());
    }

    /** Modelo que respondió la última llamada (el principal o el de respaldo). */
    private ?string $modeloUsado = null;

    /** Momento (microtime) en que se acaba el tiempo para responder la pregunta. */
    private float $fin = 0;

    /** true si el modelo rechazó la configuración de razonamiento: se manda sin ella. */
    private bool $sinRazonamiento = false;

    /**
     * Llama a Gemini cuidando el tiempo:
     *  - Si el principal está saturado (5xx) o no responde a tiempo, pasa de
     *    inmediato al de respaldo y recuerda por unos minutos que está
     *    saturado, para no volver a esperarlo en las siguientes preguntas.
     *  - Cada llamada usa solo el tiempo que queda: nunca se pasa del límite
     *    de PHP y siempre termina con un mensaje claro.
     */
    private function llamar(array $config, array $contenidos, string $instrucciones): array
    {
        $principal = $this->modeloUsado ?? $config['modelo'];
        $respaldo  = $config['modelo_respaldo'] ?? null;
        $modelos = array_values(array_unique(array_filter([$principal, $respaldo])));

        // Si el principal estuvo saturado hace poco, se empieza por el respaldo.
        if (count($modelos) > 1 && Cache::get(self::claveSaturado($modelos[0]))) {
            $modelos = array_reverse($modelos);
        }

        $http = null;
        $tiempoAgotado = false;

        foreach ($modelos as $i => $modelo) {
            $esUltimo = ! isset($modelos[$i + 1]);
            // Al último modelo se le da un segundo intento; a los demás no, se pasa al siguiente.
            for ($intento = 0; $intento < ($esUltimo ? 2 : 1); $intento++) {
                if ($intento > 0) {
                    usleep((int) ($config['espera_ms'] ?? 1000) * 1000);
                }
                $restante = $this->fin - microtime(true);
                if ($restante < 4) {
                    $tiempoAgotado = true;
                    break 2;
                }

                try {
                    $http = $this->enviar($config, $modelo, $contenidos, $instrucciones, (int) min($config['timeout'] ?? 30, $restante - 2));
                } catch (ConnectionException $e) {
                    if (! preg_match('/timed out|error 28/i', $e->getMessage())) {
                        throw $this->sinConexion($e);
                    }
                    $tiempoAgotado = true;      // tardó demasiado: se prueba el siguiente si queda tiempo
                    Cache::put(self::claveSaturado($modelo), true, now()->addMinutes(5));
                    continue;
                }
                $tiempoAgotado = false;

                // Algunos modelos no aceptan el nivel de razonamiento: se reintenta sin él.
                if ($http->status() === 400 && ! $this->sinRazonamiento && preg_match('/thinking/i', $http->body())) {
                    $this->sinRazonamiento = true;
                    $intento--;
                    continue;
                }
                if ($http->successful()) {
                    $this->modeloUsado = $modelo;

                    return $http->json() ?? [];
                }
                if (! in_array($http->status(), [500, 502, 503, 504], true)) {
                    break 2; // reintentar no ayuda (clave mala, límite, modelo inexistente…)
                }
                Cache::put(self::claveSaturado($modelo), true, now()->addMinutes(5));
                Log::warning('Cerberus Bot: Gemini saturado', ['modelo' => $modelo, 'status' => $http->status()]);
            }
        }

        if ($tiempoAgotado || ! $http) {
            throw new AsistenteException(
                'El asistente tardó demasiado en responder. Intenta de nuevo en un momento (o con una pregunta más concreta).',
                'Gemini no respondió dentro del tiempo (asistente.tiempo_maximo = ' . config('asistente.tiempo_maximo') . ' s).'
            );
        }

        Log::warning('Cerberus Bot: Gemini respondió con error', ['status' => $http->status(), 'body' => mb_substr($http->body(), 0, 1000)]);

        $status = $http->status();
        $detalle = match (true) {
            in_array($status, [400, 401, 403]) && str_contains($http->body(), 'API key') => 'La clave de Gemini no es válida. Revisa GEMINI_API_KEY.',
            $status === 404 => "El modelo «{$config['modelo']}» no existe o no está disponible. Revisa GEMINI_MODEL.",
            $status === 429 => 'Gemini: se alcanzó el límite de uso de la cuenta.',
            $status >= 500  => 'Gemini está saturado (pasa a menudo en el plan gratuito).',
            default         => "Gemini rechazó la consulta (error {$status}): " . mb_substr($http->json('error.message') ?? '', 0, 200),
        };

        throw new AsistenteException(match (true) {
            in_array($status, [400, 401, 403, 404]) => 'El asistente tiene un problema de configuración. Avísale al administrador.',
            $status === 429 => 'El asistente alcanzó su límite de uso por ahora. Intenta de nuevo en unos minutos.',
            $status >= 500  => 'El asistente está muy ocupado en este momento. Intenta de nuevo en unos minutos.',
            default         => 'No pude completar la consulta. Intenta de nuevo.',
        }, $detalle);
    }

    public static function claveSaturado(string $modelo): string
    {
        return "asistente:gemini-saturado:{$modelo}";
    }

    private function enviar(array $config, string $modelo, array $contenidos, string $instrucciones, int $timeout): \Illuminate\Http\Client\Response
    {
        $generacion = [];
        if (! $this->sinRazonamiento && ! empty($config['razonamiento'])) {
            // Razonamiento bajo: respuestas mucho más rápidas para consultas como estas.
            $generacion['thinkingConfig'] = ['thinkingLevel' => $config['razonamiento']];
        }

        try {
            return $this->cliente($config)->timeout(max(5, $timeout))
                ->post("{$config['url']}/models/{$modelo}:generateContent", array_filter([
                    'systemInstruction' => ['parts' => [['text' => $instrucciones]]],
                    'contents'          => $contenidos,
                    'tools'             => [['functionDeclarations' => $this->declaraciones()]],
                    'generationConfig'  => $generacion ?: null,
                ]));
        } catch (\Illuminate\Http\Client\RequestException|\GuzzleHttp\Exception\TransferException $e) {
            throw $this->sinConexion($e);
        }
    }

    private function sinConexion(\Throwable $e): AsistenteException
    {
        Log::warning('Cerberus Bot: sin conexión con Gemini', ['error' => $e->getMessage()]);

        return new AsistenteException(
            'No pude conectarme con el servicio del asistente. Intenta en un momento.',
            self::explicarFalloDeConexion($e->getMessage()),
        );
    }

    /** Cliente HTTP con la clave y, si la red lo exige, el proxy de la empresa (GEMINI_PROXY). */
    public function cliente(array $config): \Illuminate\Http\Client\PendingRequest
    {
        $http = Http::withHeaders(['x-goog-api-key' => (string) $config['api_key']])
            ->timeout($config['timeout'] ?? 60);

        return empty($config['proxy']) ? $http : $http->withOptions(['proxy' => $config['proxy']]);
    }

    /**
     * Traduce el error técnico de conexión (cURL) a qué revisar en el
     * servidor. Lo ve solo el Administrador y queda en la bitácora.
     */
    public static function explicarFalloDeConexion(string $error): string
    {
        $pista = match (true) {
            (bool) preg_match('/SSL certificate|certificate verify|local issuer|error 60|error 77/i', $error)
                => 'PHP no tiene los certificados de confianza (típico en Windows): descarga cacert.pem de curl.se/docs/caextract.html, '
                 . 'guárdalo (ej. C:\php\extras\ssl\cacert.pem) y en php.ini pon curl.cainfo y openssl.cafile con esa ruta; luego reinicia Apache.',
            (bool) preg_match('/resolve host|getaddrinfo|error 6\b/i', $error)
                => 'El servidor no logra resolver el nombre generativelanguage.googleapis.com (DNS). Revisa el DNS o si la red exige un proxy (GEMINI_PROXY).',
            (bool) preg_match('/timed out|error 28|Failed to connect|error 7\b|Connection refused/i', $error)
                => 'El servidor no llega a Google: probablemente el firewall bloquea la salida o la red exige un proxy. '
                 . 'Pide que permitan generativelanguage.googleapis.com por el puerto 443, o configura GEMINI_PROXY.',
            (bool) preg_match('/proxy/i', $error)
                => 'Falló el proxy configurado en GEMINI_PROXY: revisa la dirección, el puerto y si pide usuario.',
            default => 'Revisa la salida a internet del servidor hacia generativelanguage.googleapis.com.',
        };

        return $pista . ' Error técnico: ' . mb_substr($error, 0, 300);
    }

    private function terminar(string $texto, array $usadas, array $contenidos, string $modelo): Respuesta
    {
        $sinRespuesta = str_contains($texto, \App\Asistente\Asistente::MARCA_SIN_DATOS);
        $texto = trim(str_replace(\App\Asistente\Asistente::MARCA_SIN_DATOS, '', $texto));

        return new Respuesta(
            $texto !== '' ? $texto : 'No tengo una respuesta para eso.',
            $usadas,
            $sinRespuesta,
            $contenidos,
            $modelo,
        );
    }
}
