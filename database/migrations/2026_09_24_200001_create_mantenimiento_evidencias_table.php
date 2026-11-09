<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimiento_evidencias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mantenimiento_id')->constrained('mantenimientos')->cascadeOnDelete();
            $table->string('ruta_archivo');
            $table->enum('tipo', ['Antes', 'Durante', 'Después'])->nullable();
            $table->foreignId('subido_por_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('mantenimiento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_evidencias');
    }
};
