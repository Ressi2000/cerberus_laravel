<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Se quita el criterio "gama básica": solo miraba el nombre del procesador
 * (Celeron, Pentium, Athlon…) y no decía si el equipo sirve o no. Lo que
 * importa ya lo miden las otras reglas (edad, Windows, RAM, disco, reparaciones).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('criterios_renovacion', function (Blueprint $table) {
            $table->dropColumn('gama_basica');
        });
    }

    public function down(): void
    {
        Schema::table('criterios_renovacion', function (Blueprint $table) {
            $table->string('gama_basica', 10)->default('ignorar')->after('ryzen_serie_minima');
        });
    }
};
