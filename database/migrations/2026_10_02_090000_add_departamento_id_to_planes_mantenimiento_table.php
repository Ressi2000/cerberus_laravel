<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite acotar un plan a un departamento específico (dentro de la
 * categoría+empresa), no solo "todos" — pensado para organizar mejor la
 * logística de la jornada (los usuarios de un depto entregan su equipo,
 * no toda la empresa a la vez).
 *
 * Plano/sin ->constrained(): ALTER con FK en SQLite sobre una tabla
 * existente dispara un rebuild completo con riesgo de perder columnas —
 * la integridad queda a nivel de aplicación, igual que deposito_id en
 * equipos/componentes_almacen.
 *
 * El unique(empresa_id, categoria_id) original se reemplaza por uno que
 * incluye departamento_id: permite un plan "todos los departamentos"
 * (departamento_id NULL) y, además, un plan por cada departamento
 * específico dentro de la misma categoría+empresa.
 *
 * IMPORTANTE (MySQL/InnoDB): el índice unique(empresa_id, categoria_id)
 * original es el único índice que empieza por empresa_id, así que InnoDB
 * lo necesita para respaldar la foreign key de esa columna — soltarlo
 * ANTES de tener otro índice que también empiece por empresa_id falla con
 * el error 1553 ("needed in a foreign key constraint"). Por eso el nuevo
 * unique se crea PRIMERO y el viejo se suelta DESPUÉS. Cada paso está
 * envuelto en try/catch porque esta migración falló a mitad de camino en
 * MySQL antes de este fix (dejó la columna creada pero no el cambio de
 * índice) — así el reintento no choca con lo que ya haya quedado aplicado.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('planes_mantenimiento', 'departamento_id')) {
            Schema::table('planes_mantenimiento', function (Blueprint $table) {
                $table->unsignedBigInteger('departamento_id')->nullable()->after('categoria_id');
                $table->index('departamento_id');
            });
        }

        try {
            Schema::table('planes_mantenimiento', function (Blueprint $table) {
                $table->unique(
                    ['empresa_id', 'categoria_id', 'departamento_id'],
                    'planes_mantenimiento_empresa_categoria_departamento_unique'
                );
            });
        } catch (\Throwable $e) {
            // Ya existe (reintento tras una corrida parcial) — seguir.
        }

        try {
            Schema::table('planes_mantenimiento', function (Blueprint $table) {
                $table->dropUnique('planes_mantenimiento_empresa_id_categoria_id_unique');
            });
        } catch (\Throwable $e) {
            // Ya no existe, o nunca existió — seguir.
        }
    }

    public function down(): void
    {
        try {
            Schema::table('planes_mantenimiento', function (Blueprint $table) {
                $table->unique(['empresa_id', 'categoria_id'], 'planes_mantenimiento_empresa_id_categoria_id_unique');
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('planes_mantenimiento', function (Blueprint $table) {
                $table->dropUnique('planes_mantenimiento_empresa_categoria_departamento_unique');
            });
        } catch (\Throwable $e) {
        }

        if (Schema::hasColumn('planes_mantenimiento', 'departamento_id')) {
            Schema::table('planes_mantenimiento', function (Blueprint $table) {
                $table->dropIndex(['departamento_id']);
                $table->dropColumn('departamento_id');
            });
        }
    }
};
