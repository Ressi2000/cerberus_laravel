<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ahora pueden coexistir varios planes para la misma empresa+categoría+
 * departamento (para dividir un universo grande de equipos en tandas con
 * fechas propias, cada uno con sus propios "equipos puntuales") — ver
 * PlanMantenimientoModal::detectarConflictoEquipos(), que valida a nivel
 * de aplicación que esos planes no compartan ningún equipo. La restricción
 * UNIQUE de la base de datos sobre (empresa_id, categoria_id,
 * departamento_id) quedó de antes de esa decisión y ahora bloquea
 * exactamente lo que se quiere permitir.
 *
 * IMPORTANTE (MySQL/InnoDB): ese unique es el único índice que empieza por
 * empresa_id, así que InnoDB lo necesita para respaldar la foreign key de
 * esa columna — soltarlo sin tener antes otro índice que también empiece
 * por empresa_id falla con el error 1553 ("needed in a foreign key
 * constraint"), igual que pasó con el unique anterior. Por eso el nuevo
 * índice (no-unique) se crea PRIMERO y el unique se suelta DESPUÉS.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('planes_mantenimiento', function (Blueprint $table) {
                $table->index(
                    ['empresa_id', 'categoria_id', 'departamento_id'],
                    'planes_mantenimiento_empresa_categoria_departamento_index'
                );
            });
        } catch (\Throwable $e) {
            // Ya existe (reintento) — seguir.
        }

        try {
            Schema::table('planes_mantenimiento', function (Blueprint $table) {
                $table->dropUnique('planes_mantenimiento_empresa_categoria_departamento_unique');
            });
        } catch (\Throwable $e) {
            // Ya no existe, o nunca existió — seguir.
        }
    }

    public function down(): void
    {
        try {
            Schema::table('planes_mantenimiento', function (Blueprint $table) {
                $table->unique(
                    ['empresa_id', 'categoria_id', 'departamento_id'],
                    'planes_mantenimiento_empresa_categoria_departamento_unique'
                );
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('planes_mantenimiento', function (Blueprint $table) {
                $table->dropIndex('planes_mantenimiento_empresa_categoria_departamento_index');
            });
        } catch (\Throwable $e) {
        }
    }
};
