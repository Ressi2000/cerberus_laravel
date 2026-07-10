<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega departamento_id y la FK de empresa_id a `cargos`.
     *
     * Antes esta migración volvía a hacer Schema::create('cargos', ...),
     * duplicando la migración anterior (create_cargos_table de 2025-11-02)
     * sin ningún drop entre medio — en un migrate desde cero fallaba con
     * "table already exists". Se convierte en un alter idempotente.
     */
    public function up(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            if (! Schema::hasColumn('cargos', 'departamento_id')) {
                $table->foreignId('departamento_id')
                    ->nullable()
                    ->after('empresa_id')
                    ->constrained('departamentos')
                    ->nullOnDelete();
            }
        });

        // La migración original de 2025-11-02 crea empresa_id sin FK.
        try {
            Schema::table('cargos', function (Blueprint $table) {
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
        Schema::table('cargos', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
        });

        if (Schema::hasColumn('cargos', 'departamento_id')) {
            Schema::table('cargos', function (Blueprint $table) {
                $table->dropForeign(['departamento_id']);
                $table->dropColumn('departamento_id');
            });
        }
    }
};
