<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * ── Cambios en la tabla asignaciones ────────────────────────────────────
     *
     * 1. Estado: enum('Activa','Parcial','Cerrada') → enum('Activa','Cerrada')
     *    Los registros con estado 'Parcial' se migran a 'Activa' porque aún
     *    tienen items activos — es el estado correcto bajo la nueva lógica.
     *
     * 2. Receptor área común: quitar ubicacion_destino_id (era "ubicación física")
     *    y agregar los tres campos con semántica real:
     *      - area_empresa_id       → empresa del área
     *      - area_departamento_id  → departamento del área
     *      - area_responsable_id   → usuario responsable del área
     *
     *    usuario_id y los tres campos de área siguen siendo mutuamente
     *    excluyentes — solo uno de los dos grupos estará poblado.
     */
    public function up(): void
    {
        // ── Paso 1: Migrar datos ANTES de cambiar el enum ────────────────────
        // MySQL no permite cambiar el enum con valores que no existen en el nuevo
        // conjunto, así que primero actualizamos los datos.
        DB::statement("UPDATE asignaciones SET estado = 'Activa' WHERE estado = 'Parcial'");
 
        // ── Paso 2: Modificar columnas ───────────────────────────────────────
        Schema::table('asignaciones', function (Blueprint $table) {
 
            // Cambiar el enum (ahora sin 'Parcial')
            // DB::statement es necesario porque Blueprint::enum() no admite CHANGE
            // en todos los drivers sin el paquete doctrine/dbal.
        });
 
        // Cambio de enum via DDL directo (más seguro que Blueprint en MySQL)
        DB::statement("ALTER TABLE asignaciones MODIFY COLUMN estado ENUM('Activa','Cerrada') NOT NULL DEFAULT 'Activa'");
 
        Schema::table('asignaciones', function (Blueprint $table) {
 
            // ── Quitar ubicacion_destino_id ──────────────────────────────────
            // Primero eliminar la FK, luego la columna
            if (Schema::hasColumn('asignaciones', 'ubicacion_destino_id')) {
                $table->dropForeign(['ubicacion_destino_id']);
                $table->dropColumn('ubicacion_destino_id');
            }
 
            // ── Agregar campos de área común ─────────────────────────────────
            $table->foreignId('area_empresa_id')
                ->nullable()
                ->after('usuario_id')
                ->constrained('empresas')
                ->nullOnDelete();
 
            $table->foreignId('area_departamento_id')
                ->nullable()
                ->after('area_empresa_id')
                ->constrained('departamentos')
                ->nullOnDelete();
 
            $table->foreignId('area_responsable_id')
                ->nullable()
                ->after('area_departamento_id')
                ->constrained('users')
                ->nullOnDelete();
 
            // ── Índice adicional para consultas por responsable ───────────────
            $table->index('area_responsable_id');
        });
    }
 
    public function down(): void
    {
        // Restaurar estado enum original
        DB::statement("UPDATE asignaciones SET estado = 'Activa' WHERE estado NOT IN ('Activa','Parcial','Cerrada')");
        DB::statement("ALTER TABLE asignaciones MODIFY COLUMN estado ENUM('Activa','Parcial','Cerrada') NOT NULL DEFAULT 'Activa'");
 
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->dropForeign(['area_responsable_id']);
            $table->dropForeign(['area_departamento_id']);
            $table->dropForeign(['area_empresa_id']);
            $table->dropIndex(['area_responsable_id']);
            $table->dropColumn(['area_empresa_id', 'area_departamento_id', 'area_responsable_id']);
 
            // Restaurar ubicacion_destino_id
            $table->foreignId('ubicacion_destino_id')
                ->nullable()
                ->after('usuario_id')
                ->constrained('ubicaciones')
                ->nullOnDelete();
        });
    }
};
