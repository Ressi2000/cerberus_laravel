<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * MantenimientoPermisosSeeder
 *
 * Permisos del módulo de Mantenimientos y Reparaciones, y del almacén de
 * componentes que lo respalda. Administrador y Analista gestionan ambos
 * dentro de su empresa activa; dar de baja un equipo sin solución queda
 * reservado a Administrador vía MantenimientoPolicy::aprobarBaja().
 *
 * Ejecución:
 *   php artisan db:seed --class=MantenimientoPermisosSeeder
 *
 * IMPORTANTE: Spatie cachea permisos 24h.
 * Después de correr este seeder en producción ejecutar:
 *   php artisan permission:cache-reset
 */
class MantenimientoPermisosSeeder extends Seeder
{
    private array $permisos = [
        'ver mantenimientos'      => 'Ver listado y detalle de mantenimientos y reparaciones',
        'crear mantenimientos'    => 'Registrar nuevos mantenimientos y reparaciones',
        'editar mantenimientos'   => 'Editar, diagnosticar y cerrar casos de mantenimiento/reparación',
        'eliminar mantenimientos' => 'Eliminación administrativa de registros (soft delete)',
        'ver almacen'             => 'Ver el almacén de componentes',
        'crear almacen'           => 'Dar de alta componentes y registrar entradas/salidas de stock',
        'editar almacen'          => 'Editar componentes del almacén',
        'eliminar almacen'        => 'Desactivar componentes del almacén',
        'ver planes'              => 'Ver el cronograma de mantenimiento preventivo',
        'crear planes'            => 'Crear planes de mantenimiento preventivo por categoría de equipos',
        'editar planes'           => 'Editar planes de mantenimiento preventivo',
        'eliminar planes'         => 'Desactivar planes de mantenimiento preventivo',
    ];

    private array $asignacionesPorRol = [
        'Administrador' => [
            'ver mantenimientos', 'crear mantenimientos', 'editar mantenimientos', 'eliminar mantenimientos',
            'ver almacen', 'crear almacen', 'editar almacen', 'eliminar almacen',
            'ver planes', 'crear planes', 'editar planes', 'eliminar planes',
        ],
        'Analista' => [
            'ver mantenimientos', 'crear mantenimientos', 'editar mantenimientos',
            'ver almacen', 'crear almacen', 'editar almacen',
            'ver planes', 'crear planes', 'editar planes',
        ],
    ];

    public function run(): void
    {
        foreach ($this->permisos as $nombre => $descripcion) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
            $this->command->line("  <fg=green>✓</> Permiso: <comment>{$nombre}</comment>");
        }

        foreach ($this->asignacionesPorRol as $rolNombre => $permisosDelRol) {
            $rol = Role::where('name', $rolNombre)->first();

            if (! $rol) {
                $this->command->warn("  Rol '{$rolNombre}' no encontrado — omitido.");
                continue;
            }

            $rol->givePermissionTo($permisosDelRol);
            $this->command->line("  <fg=blue>→</> Rol <comment>{$rolNombre}</comment>: " . implode(', ', $permisosDelRol));
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('');
        $this->command->info('Permisos de mantenimientos y almacén registrados correctamente.');
        $this->command->warn('Recuerda: en producción ejecuta → php artisan permission:cache-reset');
    }
}
