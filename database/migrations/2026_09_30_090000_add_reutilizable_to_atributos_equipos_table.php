<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un atributo marcado como reutilizable indica que, cuando se extrae de un
 * equipo (por baja o por sustitución en una reparación), puede rescatarse
 * como pieza para el Almacén de Componentes si está en buen estado. Es una
 * propiedad del atributo en sí (ej. "Memoria RAM" siempre es reutilizable),
 * no algo que se decide equipo por equipo — mismo criterio que
 * requerido/filtrable, que ya existen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atributos_equipos', function (Blueprint $table) {
            $table->boolean('reutilizable')->default(false)->after('filtrable');
        });
    }

    public function down(): void
    {
        Schema::table('atributos_equipos', function (Blueprint $table) {
            $table->dropColumn('reutilizable');
        });
    }
};
