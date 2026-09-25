<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de tareas base de mantenimiento preventivo, configurable por el
 * Administrador. Es la fuente de defaults al armar la checklist de un plan
 * de cronograma o de un caso manual — en cada uno se puede igual agregar o
 * quitar tareas puntuales sin tocar el catálogo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tareas_mantenimiento_catalogo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas_mantenimiento_catalogo');
    }
};
