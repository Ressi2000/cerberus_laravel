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

            // Opciones para atributos de tipo 'select'
            // Ejemplo: ["Rojo", "Azul", "Verde"]  o  {"1":"Sí","0":"No"}
            $table->json('opciones')
                ->nullable()
                ->after('orden')
                ->comment('Opciones disponibles para atributos tipo select. JSON array o key-value.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atributos_equipos', function (Blueprint $table) {
            $table->dropColumn('opciones');
        });
    }
};
