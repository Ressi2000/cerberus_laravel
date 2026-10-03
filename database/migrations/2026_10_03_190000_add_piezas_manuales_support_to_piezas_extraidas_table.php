<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extiende piezas_extraidas para que también pueda representar una pieza
 * registrada a mano en Almacén (bisagras, carcasas, cualquier componente que
 * no es un atributo del equipo) — no solo las que salen de un atributo EAV.
 *
 * equipo_origen_id y atributo_id pasan a nullable:
 *   - atributo_id null  → la pieza nació de un registro manual en
 *     ComponenteAlmacen, no de un atributo de equipo.
 *   - equipo_origen_id null → la pieza es nueva (comprada), nunca estuvo
 *     instalada en ningún equipo.
 *
 * `condicion` distingue nuevo/reutilizado independientemente de si tiene
 * atributo — una bisagra rescatada de un equipo dado de baja es
 * "reutilizado" aunque "bisagra" nunca fue un atributo EAV formal.
 * `identificador` es un serial/código manual, opcional, para piezas que no
 * tienen otro dato que las distinga (ver PiezaExtraida::identificador()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('piezas_extraidas', function (Blueprint $table) {
            $table->dropForeign(['equipo_origen_id']);
            $table->dropForeign(['atributo_id']);
        });

        Schema::table('piezas_extraidas', function (Blueprint $table) {
            $table->unsignedBigInteger('equipo_origen_id')->nullable()->change();
            $table->unsignedBigInteger('atributo_id')->nullable()->change();
            $table->json('valor_extraido')->nullable()->change();

            $table->string('condicion', 20)->default('reutilizado')->after('reutilizable');
            $table->string('identificador', 100)->nullable()->after('condicion');
        });

        Schema::table('piezas_extraidas', function (Blueprint $table) {
            $table->foreign('equipo_origen_id')->references('id')->on('equipos')->restrictOnDelete();
            $table->foreign('atributo_id')->references('id')->on('atributos_equipos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('piezas_extraidas', function (Blueprint $table) {
            $table->dropColumn(['condicion', 'identificador']);
        });
    }
};
