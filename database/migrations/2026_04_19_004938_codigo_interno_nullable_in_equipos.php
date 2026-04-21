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
            // codigo_interno: nullable para el doble paso de generación automática
            $table->string('codigo_interno')->nullable()->change();

            // nombre_maquina: ahora es único globalmente (viene del Active Directory)
            // nullable se mantiene porque no todos los equipos tienen hostname
            $table->string('nombre_maquina')->nullable()->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('codigo_interno')->nullable(false)->change();
            $table->string('nombre_maquina')->nullable()->change(); // quitar unique
        });
    }
};
