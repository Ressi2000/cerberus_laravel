<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kardex de una pieza extraída: cada cambio de estado/ubicación queda
 * registrado aquí (extracción, ingreso a almacén, instalación en otro
 * equipo, desinstalación, envío a depósito, traslado entre depósitos...).
 * Es lo que arma la "ficha" de trazabilidad que se muestra en el equipo
 * de origen y en el histórico de la pieza — mismo patrón que
 * movimientos_componentes para el stock agregado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piezas_extraidas_movimientos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pieza_id')->constrained('piezas_extraidas')->restrictOnDelete();

            $table->string('tipo', 30);
            // extraccion | ingreso_almacen | instalacion | desinstalacion | envio_deposito | traslado_deposito

            $table->foreignId('equipo_relacionado_id')->nullable()
                ->constrained('equipos')->nullOnDelete();
            $table->foreignId('deposito_relacionado_id')->nullable()
                ->constrained('depositos')->nullOnDelete();
            $table->foreignId('mantenimiento_id')->nullable()
                ->constrained('mantenimientos')->nullOnDelete();

            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index('pieza_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piezas_extraidas_movimientos');
    }
};
