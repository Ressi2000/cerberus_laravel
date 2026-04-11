<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Reemplazar SoftDeletes por campo `activo` en tablas maestras.
 *
 * Tablas afectadas:
 *   - categorias_equipos
 *   - estados_equipos
 *   - ubicaciones
 *   - cargos
 *   - departamentos
 *
 * Estrategia:
 *   1. Agregar columna `activo` boolean (default true).
 *   2. Convertir registros con deleted_at != null → activo = false.
 *   3. Eliminar columna `deleted_at`.
 *
 */
return new class extends Migration
{
    // ─────────────────────────────────────────────────────────────────────────
    // Tablas maestras a transformar
    // ─────────────────────────────────────────────────────────────────────────
    private array $tablas = [
        'empresas'
    ];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {

                // 1. Agregar `activo` si no existe
                if (! Schema::hasColumn($tabla, 'activo')) {
                    $table->boolean('activo')->default(true)->after('id');
                }
            });

            // 2. Marcar como inactivos los que tienen deleted_at
            if (Schema::hasColumn($tabla, 'deleted_at')) {
                DB::table($tabla)
                    ->whereNotNull('deleted_at')
                    ->update(['activo' => false]);

                // 3. Eliminar deleted_at
                Schema::table($tabla, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {

                // Restaurar softDeletes
                if (! Schema::hasColumn($tabla, 'deleted_at')) {
                    $table->softDeletes();
                }

                // Convertir inactivos → deleted_at = now() para revertir
                DB::table($tabla)
                    ->where('activo', false)
                    ->update(['deleted_at' => now()]);

                // Eliminar columna activo
                if (Schema::hasColumn($tabla, 'activo')) {
                    $table->dropColumn('activo');
                }
            });
        }
    }
};