<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 3: cuando a un caso se le entrega una pieza con identidad (por
 * unidad, con serial), se guarda CUÁL pieza fue. Así "lo usado del almacén"
 * de un caso dice el serial exacto que terminó en el equipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimiento_componentes', function (Blueprint $table) {
            $table->foreignId('pieza_id')->nullable()->after('componente_id')
                ->constrained('piezas_extraidas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mantenimiento_componentes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pieza_id');
        });
    }
};
