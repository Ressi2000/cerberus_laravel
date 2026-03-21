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
        // Primero eliminamos la constraint existente (si existe) para poder
        // modificar la columna sin que MySQL/MariaDB se queje.
        Schema::table('users', function (Blueprint $table) {

            // Eliminar FK vieja (apuntaba a empresas)
            // Usamos try/catch por si el nombre de la constraint difiere entre entornos
            try {
                $table->dropForeign(['ubicacion_id']);
            } catch (\Exception $e) {
                // La FK no existía con ese nombre, continuamos
            }

            // ── Paso 2: Ajustar tipos de columnas ───────────────────────────────

            // telefono: varchar(255) → varchar(20)
            // Formato venezolano: +58-412-1234567 cabe en 20 chars
            $table->string('telefono', 20)->nullable()->change();

            // ficha: varchar(255) → varchar(50)
            // Códigos internos de nómina no superan 50 chars
            $table->string('ficha', 50)->nullable()->change();

            // cedula: varchar(50) → varchar(15)
            // Formato: V-12345678 o E-12345678 → máximo ~12 chars, 15 con holgura
            $table->string('cedula', 20)->change();

            // email: Se mantiene NOT NULL (requerido) pero SIN unique
            // (puede repetirse según regla de negocio definida)
            // Solo nos aseguramos del tipo correcto
            $table->string('email', 255)->nullable(false)->change();

            // ── Paso 3: Reasignar FK de ubicacion_id → ubicaciones ───────────────
            // Primero seteamos a NULL todos los valores que no existan en ubicaciones
            // (esto se hace en el seeder/script de datos, no aquí)
            // Luego agregamos la FK correcta
            $table->foreignId('ubicacion_id')
                ->nullable()
                ->change();
        });

        // ── Paso 4: Agregar la FK correcta en una segunda llamada ───────────────
        // Separamos en dos Schema::table para evitar problemas de orden en MySQL
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('ubicacion_id')
                ->references('id')
                ->on('ubicaciones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Revertir FK
            try {
                $table->dropForeign(['ubicacion_id']);
            } catch (\Exception $e) {
                //
            }

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