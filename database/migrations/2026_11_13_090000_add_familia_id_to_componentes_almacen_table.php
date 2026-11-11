<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2: cada componente del almacén pertenece a una familia de piezas
 * (RAM, Discos, Bisagras…). Es la "variante" dentro de su familia.
 *
 * Queda nullable para no romper lo que ya existe: los componentes sin familia
 * se muestran aparte en el almacén para asignarles una. Los que ya tienen
 * piezas de una característica vinculada a una familia (ej. RAM) se asignan
 * aquí mismo a esa familia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('componentes_almacen', function (Blueprint $table) {
            $table->foreignId('familia_id')->nullable()->after('empresa_id')
                ->constrained('familias_componentes')->restrictOnDelete();
        });

        // Componentes cuyas piezas son todas de UNA misma familia → esa familia.
        $porComponente = DB::table('piezas_extraidas')
            ->join('atributos_equipos', 'atributos_equipos.id', '=', 'piezas_extraidas.atributo_id')
            ->whereNotNull('piezas_extraidas.componente_almacen_id')
            ->whereNotNull('atributos_equipos.familia_id')
            ->select('piezas_extraidas.componente_almacen_id as componente', 'atributos_equipos.familia_id as familia')
            ->distinct()
            ->get()
            ->groupBy('componente');

        foreach ($porComponente as $componenteId => $filas) {
            if ($filas->count() === 1) {
                DB::table('componentes_almacen')->where('id', $componenteId)->whereNull('familia_id')
                    ->update(['familia_id' => $filas->first()->familia]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('componentes_almacen', function (Blueprint $table) {
            $table->dropConstrainedForeignId('familia_id');
        });
    }
};
