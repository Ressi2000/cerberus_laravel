<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Color (hex #rrggbb) asignado a cada estado de equipo, usado para pintar
 * su badge en todo el sistema (listado de equipos, detalle, traslados...).
 * Se hace backfill de colores razonables para los estados por defecto que
 * ya existan; el resto de estados custom queda con el gris por defecto y
 * el administrador lo ajusta desde Configuración → Estados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estados_equipos', function (Blueprint $table) {
            $table->string('color', 7)->default('#64748B')->after('nombre');
        });

        $colores = [
            'Disponible'     => '#22C55E',
            'Asignado'       => '#3B82F6',
            'En préstamo'    => '#F59E0B',
            'En reparación'  => '#F97316',
            'Dado de baja'   => '#EF4444',
            'No asignable'   => '#64748B',
        ];

        foreach ($colores as $nombre => $color) {
            DB::table('estados_equipos')->where('nombre', $nombre)->update(['color' => $color]);
        }
    }

    public function down(): void
    {
        Schema::table('estados_equipos', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
