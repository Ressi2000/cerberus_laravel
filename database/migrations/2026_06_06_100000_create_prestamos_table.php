<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('analista_id')->constrained('users')->restrictOnDelete();

            // Receptor personal (mutuamente excluyente con área)
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();

            // Receptor área común
            $table->foreignId('area_empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('area_departamento_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $table->foreignId('area_responsable_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('fecha_prestamo');
            $table->date('fecha_devolucion_esperada')->nullable();
            $table->date('fecha_devolucion_real')->nullable();

            $table->enum('estado', ['Activo', 'Vencido', 'Cerrado'])->default('Activo');

            $table->text('observaciones')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Índices
            $table->index('empresa_id');
            $table->index('usuario_id');
            $table->index('estado');
            $table->index(['empresa_id', 'estado']);
            $table->index(['empresa_id', 'usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
