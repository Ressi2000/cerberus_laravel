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
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias_equipos');
            $table->foreignId('estado_id')->constrained('estados_equipos');
            $table->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones');

            $table->string('codigo_interno')->unique();
            $table->string('serial')->nullable()->unique();
            $table->string('nombre_maquina')->nullable();

            $table->date('fecha_adquisicion')->nullable();
            $table->date('fecha_garantia_fin')->nullable();

            $table->boolean('activo')->default(true);

            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'estado_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
