<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Depósito donde queda guardado un equipo dado de baja. Sin FK física —
 * mismo criterio que plan_mantenimiento_id en mantenimientos: agregar una
 * columna con ->constrained() a una tabla ya existente dispara en SQLite
 * un rebuild completo de la tabla (riesgo real de perder columnas), así
 * que la integridad queda a nivel de aplicación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->unsignedBigInteger('deposito_id')->nullable();
            $table->index('deposito_id');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropIndex(['deposito_id']);
            $table->dropColumn('deposito_id');
        });
    }
};
