<?php

namespace Database\Seeders;

use App\Models\TareaMantenimientoCatalogo;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de tareas base de mantenimiento preventivo — punto de
 * partida genérico y editable desde Configuración > Tareas de Mantenimiento.
 *
 * Ejecución:
 *   php artisan db:seed --class=TareasMantenimientoCatalogoSeeder
 */
class TareasMantenimientoCatalogoSeeder extends Seeder
{
    private array $tareas = [
        'Limpieza externa e interna (polvo, ventiladores)',
        'Revisión/cambio de pasta térmica',
        'Estado de la batería',
        'Actualización de firmware/BIOS',
        'Actualizaciones del sistema operativo',
        'Verificación de antivirus/seguridad',
        'Salud del disco (SMART) y espacio disponible',
        'Estado de cables, puertos y conexiones',
        'Prueba general de encendido y funcionamiento',
    ];

    public function run(): void
    {
        foreach ($this->tareas as $orden => $nombre) {
            TareaMantenimientoCatalogo::firstOrCreate(
                ['nombre' => $nombre],
                ['orden' => $orden + 1, 'activo' => true]
            );
        }

        $this->command->info('Catálogo de tareas de mantenimiento preventivo cargado.');
    }
}
