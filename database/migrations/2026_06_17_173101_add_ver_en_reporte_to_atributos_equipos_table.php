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
        Schema::table('atributos_equipos', function (Blueprint $table) {
            $table->boolean('ver_en_reporte')->default(true)->after('visible_en_tabla');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atributos_equipos', function (Blueprint $table) {
            $table->dropColumn('ver_en_reporte');
        });
    }
};
