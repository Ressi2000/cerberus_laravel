<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_componentes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('componente_id')->constrained('componentes_almacen')->restrictOnDelete();
            $table->foreignId('mantenimiento_id')->nullable()->constrained('mantenimientos')->nullOnDelete();

            $table->enum('tipo', ['Entrada', 'Salida']);
            $table->unsignedInteger('cantidad');
            $table->string('motivo')->nullable();

            $table->foreignId('registrado_por_id')->constrained('users')->restrictOnDelete();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index('componente_id');
            $table->index('mantenimiento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_componentes');
    }
};
