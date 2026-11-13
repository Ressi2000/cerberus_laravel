<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traslados de stock entre los almacenes de dos empresas. Cada uno queda
 * registrado con su motivo (obligatorio) y lo que se movió, para generar
 * su "Autorización de Traslado" en PDF cuando se necesite.
 *
 * variante/familia/detalle son una foto del momento: el documento dice lo
 * mismo aunque después la variante se renombre o se borre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traslados_almacen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_origen_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('empresa_destino_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('componente_origen_id')->nullable()->constrained('componentes_almacen')->nullOnDelete();
            $table->foreignId('componente_destino_id')->nullable()->constrained('componentes_almacen')->nullOnDelete();
            $table->string('variante', 255);
            $table->string('familia', 100)->nullable();
            $table->unsignedInteger('cantidad');
            $table->json('detalle')->nullable();   // {piezas: [{id, codigo, condicion}], sin_serial: n}
            $table->text('motivo');
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::table('movimientos_componentes', function (Blueprint $table) {
            $table->foreignId('traslado_almacen_id')->nullable()->after('mantenimiento_id')
                ->constrained('traslados_almacen')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_componentes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('traslado_almacen_id');
        });

        Schema::dropIfExists('traslados_almacen');
    }
};
