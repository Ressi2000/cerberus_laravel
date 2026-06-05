<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega el valor 'group' al ENUM tipo en atributos_equipos.
 * Se usa raw SQL porque Laravel no soporta cambiar ENUMs con change() en todas las versiones.
 * Compatible con MySQL y SQLite (SQLite ignora MODIFY COLUMN silenciosamente
 * ya que la columna en SQLite no usa ENUM real — el constraint lo maneja
 * la migración 2026_06_05_100001).
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
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
                    'file',
                    'group'
                ) NOT NULL DEFAULT 'string'
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
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
    }
};
