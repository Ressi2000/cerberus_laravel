<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * AsignacionPermisosSeeder
 *
 * Crea los permisos granulares del módulo de asignaciones y los asigna
 * a los roles correspondientes.
 *
 * Nomenclatura adoptada en Cerberus: "verbo recurso" en snake_case.
 * Coherente con los permisos existentes del módulo de usuarios:
 *   ver usuarios, crear usuarios, editar usuarios, eliminar usuarios
 *
 * Ejecución:
 *   php artisan db:seed --class=AsignacionPermisosSeeder
 *
 * O agregar al DatabaseSeeder / InicialSeeder:
 *   $this->call(AsignacionPermisosSeeder::class);
 *
 * IMPORTANTE: Spatie cachea permisos 24h.
 * Después de correr este seeder en producción ejecutar:
 *   php artisan permission:cache-reset
 */
class AsignacionPermisosSeeder extends Seeder
{
    /**
     * Mapa de permisos del módulo.
     * Clave  → nombre del permiso (guard 'web')
     * Valor  → descripción legible para humanos
     */
    private array $permisos = [
        'ver asignaciones'      => 'Ver listado y detalle de asignaciones',
        'crear asignaciones'    => 'Crear nuevas asignaciones (wizard + carrito)',
        'devolver asignaciones' => 'Registrar devoluciones totales o parciales',
        'eliminar asignaciones' => 'Eliminación administrativa de asignaciones (soft delete)',
    ];

    /**
     * Roles y los permisos que reciben.
     * Administrador → todos los permisos.
     * Analista       → ver, crear y devolver (NO eliminar).
     * Usuario        → ninguno (el rol Usuario no accede al módulo).
     */
    private array $asignacionesPorRol = [
        'Administrador' => [
            'ver asignaciones',
            'crear asignaciones',
            'devolver asignaciones',
            'eliminar asignaciones',
        ],
        'Analista' => [
            'ver asignaciones',
            'crear asignaciones',
            'devolver asignaciones',
        ],
    ];

    public function run(): void
    {
        // 1. Crear permisos (firstOrCreate = seguro en re-ejecuciones)
        foreach ($this->permisos as $nombre => $descripcion) {
            Permission::firstOrCreate(
                ['name' => $nombre, 'guard_name' => 'web'],
            );

            $this->command->line("  <fg=green>✓</> Permiso: <comment>{$nombre}</comment>");
        }

        // 2. Asignar permisos a roles
        foreach ($this->asignacionesPorRol as $rolNombre => $permisosDelRol) {
            $rol = Role::where('name', $rolNombre)->first();

            if (! $rol) {
                $this->command->warn("  Rol '{$rolNombre}' no encontrado — omitido.");
                continue;
            }

            // givePermissionTo hace upsert internamente, no duplica
            $rol->givePermissionTo($permisosDelRol);

            $this->command->line(
                "  <fg=blue>→</> Rol <comment>{$rolNombre}</comment>: " .
                implode(', ', $permisosDelRol)
            );
        }

        // 3. Limpiar caché de Spatie para que los cambios sean inmediatos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('');
        $this->command->info('Permisos de asignaciones registrados correctamente.');
        $this->command->warn('Recuerda: en producción ejecuta → php artisan permission:cache-reset');
    }
}