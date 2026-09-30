<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Selección explícita de equipos individuales dentro de un plan (además de
 * categoría+empresa+departamento). Cuando un plan tiene filas acá, ESOS son
 * los equipos que alcanza — no todos los de la categoría/departamento — ver
 * PlanMantenimiento::equiposAlcanzados(). Vacío = comportamiento de
 * siempre (todos los que calcen con categoría/empresa/departamento).
 *
 * El nombre del unique se indica explícito y corto: el que autogenera
 * Laravel a partir de los nombres de columna ("plan_mantenimiento_equipos_
 * plan_mantenimiento_id_equipo_id_unique", 65 caracteres) supera el límite
 * de 64 caracteres de MySQL (error 1059). Además, Schema::create() en
 * MySQL emite el CREATE TABLE y el ADD UNIQUE como sentencias separadas —
 * esta migración ya falló una vez a mitad de camino (tabla creada, unique
 * sin agregar), así que va envuelta para tolerar ese estado parcial en un
 * reintento.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plan_mantenimiento_equipos')) {
            Schema::create('plan_mantenimiento_equipos', function (Blueprint $table) {
                $table->id();

                $table->foreignId('plan_mantenimiento_id')->constrained('planes_mantenimiento')->cascadeOnDelete();
                $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();

                $table->timestamps();
            });
        }

        try {
            Schema::table('plan_mantenimiento_equipos', function (Blueprint $table) {
                $table->unique(['plan_mantenimiento_id', 'equipo_id'], 'plan_mant_equipos_plan_equipo_unique');
            });
        } catch (\Throwable $e) {
            // Ya existe (reintento tras una corrida parcial) — seguir.
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_mantenimiento_equipos');
    }
};
