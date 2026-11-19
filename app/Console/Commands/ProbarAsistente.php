<?php

namespace App\Console\Commands;

use App\Asistente\Proveedores\Gemini;
use Illuminate\Console\Command;

/**
 * Diagnóstico de Cerberus Bot: prueba la configuración y la conexión con la
 * IA desde ESTE servidor y dice qué corregir.
 *
 *   php artisan cerberus:probar-asistente
 */
class ProbarAsistente extends Command
{
    protected $signature = 'cerberus:probar-asistente';

    protected $description = 'Comprueba la configuración de Cerberus Bot y la conexión con la IA desde este servidor';

    public function handle(Gemini $gemini): int
    {
        $config = config('asistente.gemini');
        $ok = true;

        $this->line('Configuración');
        $this->linea('ASISTENTE_HABILITADO', config('asistente.habilitado') ? 'true' : 'false (el chat no se muestra)', (bool) config('asistente.habilitado'));
        $this->linea('GEMINI_API_KEY', empty($config['api_key']) ? 'falta' : 'configurada (' . strlen($config['api_key']) . ' caracteres)', ! empty($config['api_key']));
        $this->linea('GEMINI_MODEL', $config['modelo'] . ' (respaldo: ' . ($config['modelo_respaldo'] ?: '—') . ')', true);
        $this->linea('GEMINI_PROXY', $config['proxy'] ?: 'sin proxy', true);
        $this->linea('curl.cainfo (php.ini)', ini_get('curl.cainfo') ?: 'vacío', true);
        $this->linea('openssl.cafile (php.ini)', ini_get('openssl.cafile') ?: 'vacío', true);

        if (empty($config['api_key'])) {
            $this->error('Falta GEMINI_API_KEY en el .env. Después: php artisan optimize:clear');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Conexión con la IA');

        foreach (array_unique(array_filter([$config['modelo'], $config['modelo_respaldo'] ?? null])) as $modelo) {
            try {
                $r = $gemini->cliente($config)->post("{$config['url']}/models/{$modelo}:generateContent", [
                    'contents' => [['role' => 'user', 'parts' => [['text' => 'Responde solo: OK']]]],
                ]);
            } catch (\Throwable $e) {
                $this->linea($modelo, 'sin conexión', false);
                $this->warn('  ' . Gemini::explicarFalloDeConexion($e->getMessage()));

                return self::FAILURE;   // si no hay red, el otro modelo tampoco va a conectar
            }

            if ($r->successful()) {
                $texto = trim($r->json('candidates.0.content.parts.0.text') ?? '');
                $this->linea($modelo, 'responde ✔ ' . ($texto !== '' ? "(«{$texto}»)" : ''), true);
                continue;
            }

            $ok = false;
            $mensaje = $r->json('error.message') ?? mb_substr($r->body(), 0, 200);
            $this->linea($modelo, "error {$r->status()}", false);
            $this->warn('  ' . match (true) {
                in_array($r->status(), [400, 401, 403]) && str_contains($r->body(), 'API key') => 'La clave no es válida. Crea otra en Google AI Studio.',
                $r->status() === 404 => 'Ese modelo no existe o tu cuenta no tiene acceso. Prueba otro en GEMINI_MODEL.',
                $r->status() === 429 => 'Límite de uso alcanzado (plan gratuito). Espera un rato o activa facturación.',
                $r->status() >= 500  => 'Google está saturado en este momento. Intenta más tarde.',
                default              => 'Google rechazó la consulta.',
            } . " ({$mensaje})");
        }

        $this->newLine();
        $ok ? $this->info('Todo listo: el asistente puede conectarse desde este servidor.')
            : $this->warn('Revisa los puntos marcados arriba.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function linea(string $que, string $valor, bool $bien): void
    {
        $this->line(sprintf('  %s %-26s %s', $bien ? '<info>✔</info>' : '<error>✘</error>', $que, $valor));
    }
}
