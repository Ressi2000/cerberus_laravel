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
        Schema::create('atributos_equipos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('categoria_id')->constrained('categorias_equipos')->cascadeOnDelete();

            $table->string('nombre'); // RAM, Procesador, IMEI, Puertos
            $table->string('slug');   // ram, procesador, imei
            $table->enum('tipo', [
                'string',
                'integer',
                'decimal',
                'boolean',
                'date',
                'text'
            ]);

            $table->boolean('requerido')->default(false);
            $table->boolean('filtrable')->default(false);
            $table->boolean('visible_en_tabla')->default(true);
            $table->integer('orden')->default(0);

            $table->timestamps();

            $table->unique(['categoria_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atributos_equipos');
    }
};
