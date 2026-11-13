<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quita la llave foránea users.empresa_id → empresas (la empresa activa pasó
 * a empresa_activa_id y la relación usuario-empresa a la tabla empresa_user).
 *
 * La COLUMNA empresa_id se conserva: en producción nunca se borró y el código
 * la sigue usando como empresa de nómina (ver User::$fillable y CrearUsuario).
 * Por eso aquí solo se quitan la llave y su índice, y solo si existen — en una
 * base nueva users.empresa_id ya nace sin ellos (ver add_fields_to_users).
 */
return new class extends Migration
{
    public function up(): void
    {
        $tieneFk = collect(Schema::getForeignKeys('users'))
            ->contains(fn ($fk) => $fk['columns'] === ['empresa_id']);

        if ($tieneFk) {
            Schema::table('users', fn (Blueprint $table) => $table->dropForeign(['empresa_id']));
        }

        if (Schema::hasIndex('users', ['empresa_id'])) {
            Schema::table('users', fn (Blueprint $table) => $table->dropIndex(['empresa_id']));
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('empresa_id')->references('id')->on('empresas');
        });
    }
};
