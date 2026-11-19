<?php

namespace App\Console\Commands;

use App\Asistente\Herramientas;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Ejecuta una herramienta del asistente desde la consola, con los permisos
 * de un usuario, para ver exactamente qué datos recibiría la IA.
 *
 *   php artisan cerberus:herramienta                       (lista las herramientas)
 *   php artisan cerberus:herramienta ver_equipo --usuario=admin --args='{"codigo":"EQ-00057"}'
 */
class ProbarHerramienta extends Command
{
    protected $signature = 'cerberus:herramienta
        {nombre? : Herramienta a ejecutar (vacío: lista todas)}
        {--usuario= : Usuario (username, correo o id) con cuyos permisos se consulta}
        {--args={} : Parámetros en JSON}';

    protected $description = 'Prueba una herramienta de consulta del asistente de Cerberus';

    public function handle(Herramientas $herramientas): int
    {
        $nombre = $this->argument('nombre');

        if (! $nombre) {
            $this->table(['Herramienta', 'Para qué sirve'], collect($herramientas->todas())
                ->map(fn ($h) => [$h->nombre(), wordwrap($h->descripcion(), 90)])->values()->all());

            return self::SUCCESS;
        }

        $ref = (string) $this->option('usuario');
        $usuario = $ref === '' ? null : User::where('username', $ref)->orWhere('email', $ref)
            ->when(ctype_digit($ref), fn ($q) => $q->orWhere('id', (int) $ref))->first();

        if (! $usuario) {
            $this->error('Indica un usuario válido con --usuario= (las consultas respetan sus permisos).');

            return self::FAILURE;
        }

        $args = json_decode((string) $this->option('args'), true);
        if (! is_array($args)) {
            $this->error('--args debe ser un JSON válido, ej. --args=\'{"codigo":"EQ-00001"}\'');

            return self::FAILURE;
        }

        $resultado = $herramientas->ejecutar($nombre, $usuario, $args);
        $this->line(json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $resultado['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
