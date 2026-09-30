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
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->unsignedBigInteger('departamento_id')->nullable()->after('categoria_id');
            $table->index('departamento_id');
        });

        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->dropUnique(['empresa_id', 'categoria_id']);
            $table->unique(['empresa_id', 'categoria_id', 'departamento_id']);
        });
    }

    public function down(): void
    {
        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->dropUnique(['empresa_id', 'categoria_id', 'departamento_id']);
            $table->dropIndex(['departamento_id']);
            $table->dropColumn('departamento_id');
            $table->unique(['empresa_id', 'categoria_id']);
        });
    }
};
