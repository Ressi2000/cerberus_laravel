<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la FK de empresa_id a `departamentos`.
     *
     * Antes esta migración volvía a hacer Schema::create('departamentos', ...),
     * duplicando la migración anterior (create_departamentos_table de
     * 2025-11-02) sin ningún drop entre medio — en un migrate desde cero
     * fallaba con "table already exists", igual que pasó con
     * create_users_table y create_cargos_table. Se convierte en un alter
     * idempotente, mismo criterio ya aplicado en create_cargos_table.
     */
    public function up(): void
    {
        try {
            Schema::table('departamentos', function (Blueprint $table) {
                $table->foreign('empresa_id')->references('id')->on('empresas')->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // La FK ya existe en este entorno (corrió antes de esta limpieza).
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departamentos', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
        });
    }
};
