<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamo_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('prestamo_id')->constrained('prestamos')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();

            // Jerarquía padre-hijo (null = principal, ID = periférico de ese item)
            $table->foreignId('equipo_padre_id')
                ->nullable()
                ->constrained('prestamo_items')
                ->nullOnDelete();

            $table->boolean('devuelto')->default(false);
            $table->date('fecha_devolucion')->nullable();
            $table->foreignId('devuelto_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observaciones_devolucion')->nullable();

            $table->timestamps();

            // Un equipo no puede aparecer dos veces en el mismo préstamo
            $table->unique(['prestamo_id', 'equipo_id']);

            // Índices
            $table->index('prestamo_id');
            $table->index('equipo_id');
            $table->index(['equipo_id', 'devuelto']);
            $table->index('equipo_padre_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamo_items');
    }
};
