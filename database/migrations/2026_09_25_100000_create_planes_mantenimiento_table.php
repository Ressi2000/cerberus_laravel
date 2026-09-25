<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan de mantenimiento preventivo por equipo: define cada cuánto le toca
 * revisión y con qué checklist. Un comando programado (GenerarMantenimientosProgramados)
 * revisa los planes activos y crea el caso "Programado" en Mantenimientos
 * cuando se acerca la fecha, sin que el analista tenga que acordarse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes_mantenimiento', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('equipo_id')->unique()->constrained('equipos')->cascadeOnDelete();

            $table->unsignedSmallInteger('frecuencia_meses');
            $table->date('fecha_proximo');
            $table->json('checklist_plantilla')->nullable();

            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('empresa_id');
            $table->index(['activo', 'fecha_proximo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_mantenimiento');
    }
};
