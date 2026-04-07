<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Tabla: asignacion_items
     *
     * Cada fila representa UN equipo dentro de una asignación.
     * Relación 1:N con asignaciones (una asignación puede tener N equipos).
     *
     * Notas de diseño:
     *  - devuelto: false = sigue asignado, true = fue devuelto
     *  - fecha_devolucion: null hasta que se registre la devolución
     *  - devuelto_por_id: analista que registró la devolución
     *  - La combinación (asignacion_id, equipo_id) es única para evitar
     *    duplicados en una misma asignación.
     *  - No tiene SoftDeletes: las devoluciones se marcan con el flag devuelto.
     */
    public function up(): void
    {
        Schema::create('asignacion_items', function (Blueprint $table) {
            $table->id();
 
            // ── Asignación padre ──────────────────────────────────────────────
            $table->foreignId('asignacion_id')
                ->constrained('asignaciones')
                ->cascadeOnDelete();   // si se elimina la asignación, se van los items
 
            // ── Equipo asignado ───────────────────────────────────────────────
            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->restrictOnDelete();  // no se puede eliminar un equipo con asignación activa
 
            // ── Control de devolución ─────────────────────────────────────────
            $table->boolean('devuelto')->default(false);
            $table->date('fecha_devolucion')->nullable();
 
            $table->foreignId('devuelto_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
 
            $table->text('observaciones_devolucion')->nullable();
 
            // ── Control ───────────────────────────────────────────────────────
            $table->timestamps();
 
            // ── Restricción: un equipo no puede aparecer dos veces en la misma asignación
            $table->unique(['asignacion_id', 'equipo_id'], 'uq_item_asignacion_equipo');
 
            // ── Índices ───────────────────────────────────────────────────────
            $table->index('asignacion_id');
            $table->index('equipo_id');
            $table->index(['equipo_id', 'devuelto']);        // historial activo por equipo
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('asignacion_items');
    }
};
