<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El modelo Cargo nunca usó SoftDeletes (la baja lógica se maneja con
     * `activo`), pero una migración vieja le agregó `deleted_at` igual.
     * Queda como columna muerta: se elimina.
     */
    public function up(): void
    {
        if (Schema::hasColumn('cargos', 'deleted_at')) {
            Schema::table('cargos', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('cargos', 'deleted_at')) {
            Schema::table('cargos', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }
};
