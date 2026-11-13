<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sin efecto a propósito.
 *
 * Creaba una tabla "ubicacions" (nombre mal pluralizado) que la aplicación
 * nunca usó: la tabla real es "ubicaciones" (2025_12_10_202542). En
 * producción esa tabla ya no existe. Se deja el archivo, sin efecto, porque
 * las bases existentes lo tienen registrado como ejecutado.
 */
return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
