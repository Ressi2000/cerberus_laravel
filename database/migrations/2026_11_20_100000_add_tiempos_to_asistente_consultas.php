<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Cuánto tardó cada llamada a la IA y cada consulta de una pregunta (para ver dónde se va el tiempo). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistente_consultas', function (Blueprint $table) {
            $table->json('tiempos')->nullable()->after('duracion_ms');
        });
    }

    public function down(): void
    {
        Schema::table('asistente_consultas', function (Blueprint $table) {
            $table->dropColumn('tiempos');
        });
    }
};
