<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Tabla: asignaciones
     *
     * Registra asignaciones permanentes de equipos a usuarios o ubicaciones.
     * Una asignación puede tener múltiples equipos (ver asignacion_items).
     *
     * Notas de diseño:
     *  - usuario_id y ubicacion_destino_id son mutuamente excluyentes (nullable ambos).
     *    Una asignación va a UNA persona o a UN área compartida, nunca a los dos.
     *  - analista_id es el usuario autenticado que crea la asignación.
     *  - estado: 'Activa' | 'Parcial' | 'Cerrada'
     *    · Activa   → todos los items siguen asignados
     *    · Parcial  → se devolvieron algunos items pero no todos
     *    · Cerrada  → todos los items fueron devueltos
     *  - SoftDeletes para eliminación administrativa (solo Administrador).
     */
    public function up(): void
    {
        Schema::create('asignaciones', function (Blueprint $table) {
            $table->id();
 
            // ── Empresa ──────────────────────────────────────────────────────
            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->restrictOnDelete();
 
            // ── Receptor (usuario O ubicación compartida) ─────────────────────
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
 
            $table->foreignId('ubicacion_destino_id')
                ->nullable()
                ->constrained('ubicaciones')
                ->nullOnDelete();
 
            // ── Responsable ───────────────────────────────────────────────────
            $table->foreignId('analista_id')
                ->constrained('users')
                ->restrictOnDelete();
 
            // ── Datos de la asignación ────────────────────────────────────────
            $table->date('fecha_asignacion');
 
            $table->enum('estado', ['Activa', 'Parcial', 'Cerrada'])
                ->default('Activa');
 
            $table->text('observaciones')->nullable();
 
            // ── Control ───────────────────────────────────────────────────────
            $table->timestamps();
            $table->softDeletes();
 
            // ── Índices ───────────────────────────────────────────────────────
            $table->index('empresa_id');
            $table->index('usuario_id');
            $table->index('estado');
            $table->index(['empresa_id', 'estado']);         // filtro más frecuente
            $table->index(['empresa_id', 'usuario_id']);     // historial por persona
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('asignaciones');
    }
};
