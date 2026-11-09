<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Se agrega SIN constraint físico de foreign key: en SQLite, añadir una
 * columna con FK a una tabla ya existente fuerza a Laravel a reconstruir
 * la tabla completa (copiar a una tabla temporal, borrar, renombrar), y
 * en la práctica de este proyecto esa reconstrucción no está copiando el
 * resto de las columnas — un riesgo real de pérdida de datos que no vale
 * la pena correr por una sola columna nullable. La integridad se mantiene
 * a nivel de aplicación (Eloquent) via Mantenimiento::planMantenimiento()/
 * PlanMantenimiento::mantenimientos().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_mantenimiento_id')->nullable();
            $table->index('plan_mantenimiento_id');
        });
    }

    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropIndex(['plan_mantenimiento_id']);
            $table->dropColumn('plan_mantenimiento_id');
        });
    }
};
