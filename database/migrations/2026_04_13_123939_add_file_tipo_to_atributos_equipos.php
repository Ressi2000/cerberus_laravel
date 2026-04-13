<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extiende el enum `tipo` de la tabla `atributos_equipos`
 * añadiendo el valor 'file'.
 *
 * MySQL no permite ALTER COLUMN en un ENUM de forma directa sin
 * redefinir la lista completa, por eso usamos una sentencia RAW.
 *
 * El orden del enum siempre debe incluir TODOS los valores previos
 * para no romper datos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE atributos_equipos
            MODIFY COLUMN tipo ENUM(
                'string',
                'integer',
                'decimal',
                'boolean',
                'date',
                'text',
                'select',
                'file'
            ) NOT NULL DEFAULT 'string'
        ");
    }

    public function down(): void
    {
        // Revertir: quitar 'file' del enum.
        // PRECAUCIÓN: si ya existen filas con tipo='file', fallarán al revertir.
        DB::statement("
            ALTER TABLE atributos_equipos
            MODIFY COLUMN tipo ENUM(
                'string',
                'integer',
                'decimal',
                'boolean',
                'date',
                'text',
                'select'
            ) NOT NULL DEFAULT 'string'
        ");
    }
};