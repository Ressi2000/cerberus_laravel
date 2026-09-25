<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan de mantenimiento preventivo por CATEGORÍA + EMPRESA (no por equipo
 * individual — un plan es un evento masivo: "cada 6 meses, revisar todas
 * las laptops de esta empresa"). Un comando programado
 * (GenerarMantenimientosProgramados) revisa los planes activos y, cuando se
 * acerca fecha_proximo, crea un caso "Programado" en Mantenimientos por
 * cada equipo activo de esa categoría/empresa — el analista no tiene que
 * acordarse de crear cada uno ni de armar el lote a mano.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes_mantenimiento', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias_equipos')->restrictOnDelete();

            $table->unsignedSmallInteger('frecuencia_meses');
            $table->date('fecha_proximo');
            $table->json('checklist_plantilla')->nullable();

            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['empresa_id', 'categoria_id']);
            $table->index(['activo', 'fecha_proximo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_mantenimiento');
    }
};
