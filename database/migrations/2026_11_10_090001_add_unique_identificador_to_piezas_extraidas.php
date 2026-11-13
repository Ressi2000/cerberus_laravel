<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un identificador de pieza no se puede repetir dentro del mismo componente
 * del almacén (ver PiezaManualService). Las piezas sin identificador (NULL)
 * no cuentan: MySQL permite varios NULL en un índice único.
 *
 * Si ya hubiera repetidos, a partir del segundo se les agrega "-{id}" para
 * que el índice se pueda crear sin perder ninguna pieza.
 */
return new class extends Migration
{
    public function up(): void
    {
        $repetidos = DB::table('piezas_extraidas')
            ->select('componente_almacen_id', 'identificador')
            ->whereNotNull('identificador')
            ->groupBy('componente_almacen_id', 'identificador')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($repetidos as $r) {
            DB::table('piezas_extraidas')
                ->where('componente_almacen_id', $r->componente_almacen_id)
                ->where('identificador', $r->identificador)
                ->orderBy('id')
                ->skip(1)->take(PHP_INT_MAX)
                ->pluck('id')
                ->each(fn ($id) => DB::table('piezas_extraidas')
                    ->where('id', $id)
                    ->update(['identificador' => DB::raw("CONCAT(identificador, '-', id)")]));
        }

        Schema::table('piezas_extraidas', function (Blueprint $table) {
            $table->unique(['componente_almacen_id', 'identificador']);
        });
    }

    public function down(): void
    {
        Schema::table('piezas_extraidas', function (Blueprint $table) {
            $table->dropUnique(['componente_almacen_id', 'identificador']);
        });
    }
};
