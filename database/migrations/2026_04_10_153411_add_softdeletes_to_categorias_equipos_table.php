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
        Schema::table('categorias_equipos', function (Blueprint $table) {
            // Añadir columnas que puedan faltar
            if (! Schema::hasColumn('categorias_equipos', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categorias_equipos', function (Blueprint $table) {
            //
        });
    }
};
