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
        Schema::create('equipo_atributo_valores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('equipo_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('atributo_id')
                ->constrained('atributos_equipos')
                ->cascadeOnDelete();

            $table->text('valor');

            $table->boolean('es_actual')->default(true);

            $table->foreignId('creado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['equipo_id', 'atributo_id']);
            $table->index(['equipo_id', 'atributo_id', 'es_actual']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipo_atributo_valores');
    }
};
