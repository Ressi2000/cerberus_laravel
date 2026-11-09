<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categorias_equipos', function (Blueprint $table) {
            // Periodo de permanencia recomendado antes de evaluar una rotación
            // de asignación (NO es vida útil/obsolescencia del equipo, sino
            // cuánto tiempo es razonable que la misma persona tenga el mismo
            // equipo antes de revisar si conviene cambiarlo). Nulo = sin
            // política de rotación para esta categoría.
            $table->unsignedSmallInteger('meses_rotacion_asignacion')->nullable()->after('asignable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categorias_equipos', function (Blueprint $table) {
            $table->dropColumn('meses_rotacion_asignacion');
        });
    }
};
