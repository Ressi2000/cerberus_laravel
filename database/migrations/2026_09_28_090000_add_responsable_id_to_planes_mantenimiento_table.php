<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Responsable por defecto del plan — se hereda en cada caso que el
 * cronograma genera automáticamente (antes solo existía este campo en el
 * formulario manual de Mantenimiento).
 *
 * Sin FK física: mismo criterio que plan_mantenimiento_id en
 * mantenimientos — evita el rebuild de tabla completa que dispara SQLite
 * al agregar una columna con ->constrained() sobre una tabla ya existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->unsignedBigInteger('responsable_id')->nullable()->after('duracion_dias_estimada');
            $table->index('responsable_id');
        });
    }

    public function down(): void
    {
        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->dropColumn('responsable_id');
        });
    }
};
