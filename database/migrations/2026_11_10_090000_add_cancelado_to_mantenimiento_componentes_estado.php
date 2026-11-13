<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega "Cancelado" a mantenimiento_componentes.estado: al cerrar, completar,
 * cancelar o dar de baja un caso, los componentes que seguían "Pendiente"
 * pasan a "Cancelado" (ver Mantenimiento::cancelarComponentesPendientes()).
 *
 * Los casos ya terminados que quedaron con pendientes colgados se corrigen
 * aquí mismo, y se apaga su bandera esperando_componente.
 */
return new class extends Migration
{
    private const TERMINALES = ['Completado', 'Cerrado', 'Dado de baja', 'Cancelado'];

    public function up(): void
    {
        Schema::table('mantenimiento_componentes', function (Blueprint $table) {
            $table->enum('estado', ['Pendiente', 'Entregado', 'Cancelado'])->default('Pendiente')->change();
        });

        $casosTerminados = DB::table('mantenimientos')->whereIn('estado', self::TERMINALES)->pluck('id');

        DB::table('mantenimiento_componentes')
            ->whereIn('mantenimiento_id', $casosTerminados)
            ->where('estado', 'Pendiente')
            ->update(['estado' => 'Cancelado', 'updated_at' => now()]);

        DB::table('mantenimientos')
            ->whereIn('id', $casosTerminados)
            ->update(['esperando_componente' => false]);
    }

    public function down(): void
    {
        DB::table('mantenimiento_componentes')
            ->where('estado', 'Cancelado')
            ->update(['estado' => 'Pendiente']);

        Schema::table('mantenimiento_componentes', function (Blueprint $table) {
            $table->enum('estado', ['Pendiente', 'Entregado'])->default('Pendiente')->change();
        });
    }
};
