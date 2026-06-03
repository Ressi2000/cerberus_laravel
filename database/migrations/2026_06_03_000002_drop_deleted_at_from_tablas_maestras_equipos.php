<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina la columna deleted_at de las tablas maestras de equipos.
 * Estas tablas gestionan su ciclo de vida mediante el campo `activo` (boolean),
 * por lo que deleted_at nunca fue utilizado y genera confusión.
 */
return new class extends Migration
{
    private array $tablas = [
        'categorias_equipos',
        'estados_equipos',
        'atributos_equipos',
    ];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            if (Schema::hasColumn($tabla, 'deleted_at')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            if (! Schema::hasColumn($tabla, 'deleted_at')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }
};
