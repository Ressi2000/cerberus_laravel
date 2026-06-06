<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traslados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->string('numero', 30)->index(); // TRA-2026-001 por empresa

            $table->foreignId('ubicacion_origen_id')->constrained('ubicaciones')->restrictOnDelete();
            $table->foreignId('ubicacion_destino_id')->constrained('ubicaciones')->restrictOnDelete();

            $table->text('motivo');

            $table->foreignId('recibe_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('autoriza_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('realizado_por_id')->constrained('users')->restrictOnDelete();

            $table->date('fecha_traslado');
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['empresa_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traslados');
    }
};
