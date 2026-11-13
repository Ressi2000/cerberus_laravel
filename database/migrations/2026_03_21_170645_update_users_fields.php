<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration correctiva de la tabla users.
 *
 * Problemas que resuelve:
 *   1. ubicacion_id apuntaba a 'empresas' → ahora apunta a 'ubicaciones'
 *   2. telefono era varchar(255) → reducido a varchar(20)
 *   3. ficha era varchar(255)   → reducido a varchar(50)
 *   4. cedula era varchar(50)   → se mantiene pero se documenta el formato V-12345678 / E-12345678
 *   5. email no era único       → se confirma que es NOT NULL y se elimina unique si existía
 *
 * IMPORTANTE: Ejecutar con: php artisan migrate
 * Si hay datos en producción, revisar primero que ubicacion_id tenga
 * valores válidos en la tabla ubicaciones antes de agregar la FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Paso 1: Eliminar la FK incorrecta de ubicacion_id → empresas ────────
        // Solo si existe: en una base nueva ubicacion_id nace sin FK (ver
        // add_fields_to_users). Un try/catch dentro de Schema::table() no
        // sirve para esto: el ALTER se ejecuta al salir del closure.
        $fkUbicacion = collect(Schema::getForeignKeys('users'))
            ->contains(fn ($fk) => $fk['columns'] === ['ubicacion_id']);

        if ($fkUbicacion) {
            Schema::table('users', fn (Blueprint $table) => $table->dropForeign(['ubicacion_id']));
        }

        // ── Paso 2: Ajustar tipos de columnas ───────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            // telefono: varchar(255) → varchar(20). Formato +58-412-1234567.
            $table->string('telefono', 20)->nullable()->change();

            // ficha: varchar(255) → varchar(50). Códigos de nómina.
            $table->string('ficha', 50)->nullable()->change();

            // cedula: varchar(50) → varchar(20). Formato V-12345678 / E-12345678.
            $table->string('cedula', 20)->change();

            // email: NOT NULL.
            $table->string('email', 255)->nullable(false)->change();

            $table->unsignedBigInteger('ubicacion_id')->nullable()->change();
        });

        // ── Paso 3: FKs correctas ──────────────────────────────────────────────
        // ubicacion_id → ubicaciones. Las de departamento_id y cargo_id las
        // creaba la migración users eliminada (2025_12_10_192911); se agregan
        // aquí, si faltan, para que una base nueva quede igual que producción.
        $fks = collect(Schema::getForeignKeys('users'))->pluck('columns');

        Schema::table('users', function (Blueprint $table) use ($fks) {
            $table->foreign('ubicacion_id')->references('id')->on('ubicaciones')->nullOnDelete();

            if (! $fks->contains(['departamento_id'])) {
                $table->foreign('departamento_id')->references('id')->on('departamentos')->nullOnDelete();
            }

            if (! $fks->contains(['cargo_id'])) {
                $table->foreign('cargo_id')->references('id')->on('cargos')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropForeign(['ubicacion_id']);

            // Restaurar tipos originales
            $table->string('telefono', 255)->nullable()->change();
            $table->string('ficha', 255)->nullable()->change();
            $table->string('cedula', 50)->change();

            // Restaurar FK original a empresas
            $table->foreign('ubicacion_id')
                ->references('id')
                ->on('empresas')
                ->nullOnDelete();
        });
    }
};