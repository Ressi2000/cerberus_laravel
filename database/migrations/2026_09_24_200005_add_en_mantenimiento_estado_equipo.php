<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nuevo estado "En mantenimiento", usado mientras un equipo tiene un
 * mantenimiento preventivo en curso — distinto de "En reparación" (correctivo)
 * y de "Dado de baja" (permanente).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('estados_equipos')->insertOrIgnore([
            'nombre'     => 'En mantenimiento',
            'color'      => '#0EA5E9',
            'activo'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('estados_equipos')->where('nombre', 'En mantenimiento')->delete();
    }
};
