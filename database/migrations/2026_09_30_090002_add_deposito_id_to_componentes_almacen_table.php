<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Depósito físico donde vive este stock de componentes. Sin FK física —
 * mismo criterio que equipos.deposito_id (ver esa migración).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('componentes_almacen', function (Blueprint $table) {
            $table->unsignedBigInteger('deposito_id')->nullable();
            $table->index('deposito_id');
        });
    }

    public function down(): void
    {
        Schema::table('componentes_almacen', function (Blueprint $table) {
            $table->dropIndex(['deposito_id']);
            $table->dropColumn('deposito_id');
        });
    }
};
