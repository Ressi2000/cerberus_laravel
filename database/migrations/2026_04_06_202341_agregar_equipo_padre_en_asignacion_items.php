<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * ── Cambios en asignacion_items ──────────────────────────────────────────
     *
     * Agregar equipo_padre_id — permite vincular periféricos a un equipo
     * principal dentro de la misma asignación.
     *
     * Ejemplo de uso:
     *   item id:1  equipo: Laptop       equipo_padre_id: null   (es el principal)
     *   item id:2  equipo: Mouse        equipo_padre_id: 1      (periférico de Laptop)
     *   item id:3  equipo: Cargador     equipo_padre_id: 1      (periférico de Laptop)
     *   item id:4  equipo: Monitor      equipo_padre_id: 1      (periférico de Laptop)
     *
     * La FK es self-referential sobre asignacion_items.
     * Se permite que apunte a items de CUALQUIER asignación del mismo usuario,
     * no solo de la misma asignación — esto cubre el caso de que el mouse
     * llegue en una asignación posterior pero pertenezca a la laptop anterior.
     *
     * nullOnDelete: si se devuelve el equipo padre, el hijo queda sin padre
     * (pero no se elimina).
     */
    public function up(): void
    {
        Schema::table('asignacion_items', function (Blueprint $table) {
            $table->foreignId('equipo_padre_id')
                ->nullable()
                ->after('equipo_id')
                ->constrained('asignacion_items')
                ->nullOnDelete();
 
            $table->index('equipo_padre_id');
        });
    }
 
    public function down(): void
    {
        Schema::table('asignacion_items', function (Blueprint $table) {
            $table->dropForeign(['equipo_padre_id']);
            $table->dropIndex(['equipo_padre_id']);
            $table->dropColumn('equipo_padre_id');
        });
    }
};
