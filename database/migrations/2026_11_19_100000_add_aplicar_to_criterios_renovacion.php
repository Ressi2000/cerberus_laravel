<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Criterios técnicos con interruptor por tipo de equipo: se puede decidir
 * que a un tipo no se le aplique el procesador, Windows, la RAM o el disco
 * (además, si su ficha no tiene ese dato, no se aplica de todas formas).
 * Vida útil y reparaciones aplican siempre (criterios generales).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('criterios_renovacion', function (Blueprint $table) {
            $table->boolean('aplicar_procesador')->default(true)->after('evaluar');
            $table->boolean('aplicar_so')->default(true)->after('aplicar_procesador');
            $table->boolean('aplicar_ram')->default(true)->after('aplicar_so');
            $table->boolean('aplicar_disco')->default(true)->after('aplicar_ram');
        });
    }

    public function down(): void
    {
        Schema::table('criterios_renovacion', function (Blueprint $table) {
            $table->dropColumn(['aplicar_procesador', 'aplicar_so', 'aplicar_ram', 'aplicar_disco']);
        });
    }
};
