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
        Schema::table('equipo_atributo_grupo_instancias', function (Blueprint $table) {
            $table->boolean('es_actual')->default(true)->after('orden');
            $table->foreignId('creado_por')->nullable()->after('es_actual')->constrained('users');

            $table->index(['equipo_id', 'atributo_id', 'es_actual'], 'egi_equipo_atributo_actual_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipo_atributo_grupo_instancias', function (Blueprint $table) {
            $table->dropForeign(['creado_por']);
            $table->dropIndex('egi_equipo_atributo_actual_idx');
            $table->dropColumn(['es_actual', 'creado_por']);
        });
    }
};
