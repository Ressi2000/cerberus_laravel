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
        Schema::table('equipos', function (Blueprint $table) {
            $table->foreignId('creado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete(); // El equipo sobrevive, el creador queda vacío
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            // Es buena práctica eliminar la relación antes que la columna
            $table->dropForeign(['creado_por']);
            $table->dropColumn('creado_por');
        });
    }
};
