<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * MonitorRedPermisosSeeder
 *
 * Permisos del Monitor de red (hosts monitoreados, historial de caídas y
 * test de velocidad). Administrador y Analista lo gestionan dentro de su
 * empresa activa; el rol Usuario no accede.
 *
 * Ejecución:
 *   php artisan db:seed --class=MonitorRedPermisosSeeder
 *
 * IMPORTANTE: Spatie cachea permisos 24h.
 * Después de correr este seeder en producción ejecutar:
 *   php artisan permission:cache-reset
 */
class MonitorRedPermisosSeeder extends Seeder
{
    private array $permisos = [
        'ver monitor red'      => 'Ver el dashboard, los hosts monitoreados y el historial de caídas',
        'crear monitor red'    => 'Agregar e importar hosts al monitoreo',
        'editar monitor red'   => 'Editar, pausar y revisar manualmente hosts monitoreados',
        'eliminar monitor red' => 'Quitar hosts del monitoreo (el historial de caídas se conserva)',
    ];

    private array $asignacionesPorRol = [
        'Administrador' => ['ver monitor red', 'crear monitor red', 'editar monitor red', 'eliminar monitor red'],
        'Analista'      => ['ver monitor red', 'crear monitor red', 'editar monitor red', 'eliminar monitor red'],
    ];

    public function run(): void
    {
        foreach ($this->permisos as $nombre => $descripcion) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
            $this->command?->line("  <fg=green>✓</> Permiso: <comment>{$nombre}</comment>");
        }

        foreach ($this->asignacionesPorRol as $rolNombre => $permisosDelRol) {
            $rol = Role::where('name', $rolNombre)->first();

            if (! $rol) {
                $this->command?->warn("  Rol '{$rolNombre}' no encontrado — omitido.");
                continue;
            }

            $rol->givePermissionTo($permisosDelRol);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('Permisos del Monitor de red registrados correctamente.');
        $this->command?->warn('Recuerda: en producción ejecuta → php artisan permission:cache-reset');
    }
}
