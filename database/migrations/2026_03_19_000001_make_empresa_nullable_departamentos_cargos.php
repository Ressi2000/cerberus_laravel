<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite departamentos y cargos globales (empresa_id = NULL)
 * y agrega la relación departamento → cargo para los selects en cascada.
 *
 * Regla de negocio:
 *   empresa_id = NULL  → global, visible en todas las empresas
 *   empresa_id = X     → exclusivo de esa empresa
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Departamentos ─────────────────────────────────────────────────────
        Schema::table('departamentos', function (Blueprint $table) {
            // Primero eliminamos la FK existente si la hay
            $table->dropForeign(['empresa_id']);

            // Hacemos empresa_id nullable (NULL = global)
            $table->foreignId('empresa_id')
                ->nullable()
                ->change()
                ->constrained('empresas')
                ->nullOnDelete();
        });

        // ── Cargos ────────────────────────────────────────────────────────────
        Schema::table('cargos', function (Blueprint $table) {
            // Eliminar FK existente de empresa_id
            $table->dropForeign(['empresa_id']);

            // empresa_id nullable
            $table->foreignId('empresa_id')
                ->nullable()
                ->change()
                ->constrained('empresas')
                ->nullOnDelete();

            // Agregar relación con departamento (nullable para no romper datos existentes)
            // Si departamento_id ya existe solo añadimos la FK
            if (!Schema::hasColumn('cargos', 'departamento_id')) {
                $table->foreignId('departamento_id')
                    ->nullable()
                    ->after('empresa_id')
                    ->constrained('departamentos')
                    ->nullOnDelete();
            } else {
                // Ya existe la columna, solo ajustamos la FK
                $table->dropForeign(['departamento_id']);
                $table->foreignId('departamento_id')
                    ->nullable()
                    ->change()
                    ->constrained('departamentos')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            $table->dropForeign(['departamento_id']);
            $table->dropForeign(['empresa_id']);
            $table->foreignId('empresa_id')->change()->constrained('empresas');
        });

        Schema::table('departamentos', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->foreignId('empresa_id')->change()->constrained('empresas');
        });
    }
};
