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
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_mantenimiento_equipos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plan_mantenimiento_id')->constrained('planes_mantenimiento')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['plan_mantenimiento_id', 'equipo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_mantenimiento_equipos');
    }
};
