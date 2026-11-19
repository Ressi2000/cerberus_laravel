<?php

namespace App\Asistente\Proveedores;

use App\Asistente\AsistenteException;
use App\Asistente\Herramientas;
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

    public function responder(User $actor, string $pregunta, array $historial, string $instrucciones): Respuesta
    {
        $config = config('asistente.gemini');
        if (empty($config['api_key'])) {
            throw new AsistenteException('Falta configurar la clave de Gemini (GEMINI_API_KEY en el .env).');
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
                throw new AsistenteException("Gemini no devolvió respuesta (motivo: {$motivo}). Intenta reformular la pregunta.");
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
                $args   = (array) ($parte['functionCall']['args'] ?? []);
                $resultado = $this->herramientas->ejecutar($nombre, $actor, $args);
                $usadas[] = ['nombre' => $nombre, 'args' => $args, 'ok' => $resultado['ok']];

                $partes[] = ['functionResponse' => array_filter([
                    'id'       => $parte['functionCall']['id'] ?? null,
                    'name'     => $nombre,
                    'response' => $resultado,
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

        throw new AsistenteException(match (true) {
            in_array($http->status(), [400, 401, 403]) && str_contains($http->body(), 'API key')
                => 'La clave de Gemini no es válida. Revisa GEMINI_API_KEY.',
            $http->status() === 404 => "El modelo «{$config['modelo']}» no existe o no está disponible. Revisa GEMINI_MODEL.",
            $http->status() === 429 => 'Se alcanzó el límite de uso de Gemini. Espera un momento e intenta de nuevo.',
            $http->status() >= 500  => 'Gemini está saturado en este momento (pasa a menudo en el plan gratuito). Intenta de nuevo en unos minutos.',
            default                 => 'Gemini rechazó la consulta (error ' . $http->status() . ').',
        });
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
            throw new AsistenteException('No pude conectarme con Gemini. Revisa la conexión a internet del servidor.');
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
