<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimiento_componentes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mantenimiento_id')->constrained('mantenimientos')->cascadeOnDelete();
            $table->foreignId('componente_id')->constrained('componentes_almacen')->restrictOnDelete();

            $table->unsignedInteger('cantidad_requerida');
            $table->enum('estado', ['Pendiente', 'Entregado'])->default('Pendiente');
            $table->date('fecha_entrega')->nullable();
            $table->foreignId('entregado_por_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('mantenimiento_id');
            $table->index('componente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_componentes');
    }
};
