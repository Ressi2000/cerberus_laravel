<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tablas = [
        'categorias_equipos',
        'estados_equipos',
        'atributos_equipos',
        'ubicaciones',
        'departamentos',
        'cargos',
    ];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            if (! Schema::hasColumn($tabla, 'activo')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->boolean('activo')->default(true)->after('id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            if (Schema::hasColumn($tabla, 'activo')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->dropColumn('activo');
                });
            }
        }
    }
};
