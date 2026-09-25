<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un lote puede tardar más de un día si son muchos equipos — esto define
 * la ventana estimada del lote (fecha_proximo -> fecha_proximo + N días),
 * usada por el calendario para mostrar el evento como un rango en vez de
 * un solo día.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->unsignedSmallInteger('duracion_dias_estimada')->default(1)->after('fecha_proximo');
        });
    }

    public function down(): void
    {
        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->dropColumn('duracion_dias_estimada');
        });
    }
};
