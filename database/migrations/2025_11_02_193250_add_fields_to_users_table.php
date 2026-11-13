<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Completa la tabla users con los campos propios de Cerberus.
     *
     * Estos campos los creaba antes la migración 2025_12_10_192911_create_users_table,
     * que se eliminó del repositorio (commit aa9cf49) por duplicar la tabla users.
     * Las bases existentes ya la corrieron; esta versión hace que una base NUEVA
     * quede igual que producción (username, cedula, jefe_id, estado Activo/Inactivo).
     *
     * Las llaves foráneas a departamentos, cargos y ubicaciones se agregan en
     * 2026_03_21_170645_update_users_fields: esas tablas todavía no existen aquí.
     * users.empresa_id (empresa de nómina) queda sin llave foránea, igual que en
     * producción — ver 2026_01_15_180915_remove_empresa_id_from_users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')->after('id');
            $table->string('username', 50)->unique()->after('name');
            $table->string('ficha')->nullable()->after('email');
            $table->string('cedula', 50)->after('ficha');
            $table->unsignedBigInteger('departamento_id')->nullable()->after('cedula');
            $table->unsignedBigInteger('cargo_id')->nullable()->after('departamento_id');
            $table->unsignedBigInteger('ubicacion_id')->nullable()->after('cargo_id');
            $table->string('telefono')->nullable()->after('ubicacion_id');
            $table->foreignId('jefe_id')->nullable()->after('telefono')
                ->constrained('users')->nullOnDelete();
            $table->string('foto')->nullable()->after('jefe_id');
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo')->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['jefe_id']);
            $table->dropUnique(['username']);
            $table->dropColumn([
                'empresa_id', 'username', 'ficha', 'cedula', 'departamento_id', 'cargo_id',
                'ubicacion_id', 'telefono', 'jefe_id', 'foto', 'estado',
            ]);
        });
    }
};
