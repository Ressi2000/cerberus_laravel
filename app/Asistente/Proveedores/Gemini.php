<?php

namespace App\Asistente\Proveedores;

use App\Asistente\AsistenteException;
use App\Asistente\Herramientas;
use App\Asistente\Privacidad;
use App\Asistente\Respuesta;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
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
        $contenidos = $historial;
        $contenidos[] = ['role' => 'user', 'parts' => [['text' => $pregunta]]];
        $usadas = [];

        for ($paso = 0; $paso < config('asistente.max_pasos', 8); $paso++) {
            $respuesta = $this->llamar($config, $contenidos, $instrucciones);
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

                // "_adjunto" (ej. el enlace de un informe) es para la pantalla, no para la IA.
                $adjunto = $resultado['datos']['_adjunto'] ?? null;
                unset($resultado['datos']['_adjunto']);
                $usadas[] = array_filter(['nombre' => $nombre, 'args' => $args, 'ok' => $resultado['ok'], 'adjunto' => $adjunto], fn ($v) => $v !== null);

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

    /**
     * Llama a Gemini. Si el modelo está saturado (500/503), reintenta unas
     * veces y, si sigue así (o el modelo no existe), prueba el de respaldo.
     */
    private function llamar(array $config, array $contenidos, string $instrucciones): array
    {
        $modelos = array_values(array_unique(array_filter([
            $this->modeloUsado ?? $config['modelo'],
            $config['modelo_respaldo'] ?? null,
        ])));
        $http = null;

        foreach ($modelos as $i => $modelo) {
            for ($intento = 0; $intento <= ($config['reintentos'] ?? 2); $intento++) {
                if ($intento > 0) {
                    usleep((int) (($config['espera_ms'] ?? 1500) * 1000 * $intento));
                }

                $http = $this->enviar($config, $modelo, $contenidos, $instrucciones);

                if ($http->successful()) {
                    $this->modeloUsado = $modelo;

                    return $http->json() ?? [];
                }
                if (! in_array($http->status(), [500, 502, 503, 504], true)) {
                    break; // reintentar no ayuda (clave mala, límite, modelo inexistente…)
                }
            }

            $siguiente = isset($modelos[$i + 1]) && in_array($http->status(), [404, 500, 502, 503, 504], true);
            Log::warning('Cerberus Bot: Gemini respondió con error', [
                'modelo' => $modelo, 'status' => $http->status(), 'body' => mb_substr($http->body(), 0, 1000),
            ]);
            if (! $siguiente) {
                break;
            }
        }

        $status = $http->status();
        $detalle = match (true) {
            in_array($status, [400, 401, 403]) && str_contains($http->body(), 'API key') => 'La clave de Gemini no es válida. Revisa GEMINI_API_KEY.',
            $status === 404 => "El modelo «{$config['modelo']}» no existe o no está disponible. Revisa GEMINI_MODEL.",
            $status === 429 => 'Gemini: se alcanzó el límite de uso de la cuenta.',
            $status >= 500  => 'Gemini está saturado (pasa a menudo en el plan gratuito).',
            default         => "Gemini rechazó la consulta (error {$status}).",
        };

        throw new AsistenteException(match (true) {
            in_array($status, [400, 401, 403, 404]) => 'El asistente tiene un problema de configuración. Avísale al administrador.',
            $status === 429 => 'El asistente alcanzó su límite de uso por ahora. Intenta de nuevo en unos minutos.',
            $status >= 500  => 'El asistente está muy ocupado en este momento. Intenta de nuevo en unos minutos.',
            default         => 'No pude completar la consulta. Intenta de nuevo.',
        }, $detalle);
    }

    private function enviar(array $config, string $modelo, array $contenidos, string $instrucciones): \Illuminate\Http\Client\Response
    {
        try {
            return Http::withHeaders(['x-goog-api-key' => $config['api_key']])
                ->timeout($config['timeout'] ?? 60)
                ->post("{$config['url']}/models/{$modelo}:generateContent", [
                    'systemInstruction' => ['parts' => [['text' => $instrucciones]]],
                    'contents'          => $contenidos,
                    'tools'             => [['functionDeclarations' => $this->declaraciones()]],
                    'generationConfig'  => ['temperature' => 0.2],
                ]);
        } catch (ConnectionException $e) {
            throw new AsistenteException('No pude conectarme con el servicio del asistente. Intenta en un momento.', 'Sin conexión con Gemini: revisa la salida a internet del servidor.');
        }
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
