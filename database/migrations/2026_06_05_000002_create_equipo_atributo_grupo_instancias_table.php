<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipo_atributo_grupo_instancias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('atributo_id')->constrained('atributos_equipos')->cascadeOnDelete();
            // valores: {"uuid-sub-campo-1": "SSD", "uuid-sub-campo-2": "512GB"}
            $table->json('valores');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['equipo_id', 'atributo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_atributo_grupo_instancias');
    }
};
