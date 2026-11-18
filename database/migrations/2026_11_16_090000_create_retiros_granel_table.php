<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traza de lo A GRANEL que sale de donde estaba (no tiene serial, así que
 * no vive en piezas_extraidas):
 *
 * - devolucion: se registró de más en un caso (error) → vuelve al almacén.
 * - retiro:     estaba puesto en un equipo y se saca → almacén, depósito o desecho.
 * - danado:     se dañó estando en el almacén → depósito o desecho.
 *
 * Con esto se sabe cuánto hay puesto en cada equipo (lo usado en sus casos
 * menos devoluciones y retiros) y qué hay a granel en cada depósito.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retiros_granel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('componente_id')->nullable()->constrained('componentes_almacen')->nullOnDelete();
            $table->string('variante', 255);                 // foto del nombre
            $table->string('tipo', 20);                      // devolucion | retiro | danado
            $table->unsignedInteger('cantidad');
            $table->string('destino', 20);                   // almacen | deposito | desecho
            $table->foreignId('deposito_id')->nullable()->constrained('depositos')->nullOnDelete();
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();
            $table->foreignId('mantenimiento_id')->nullable()->constrained('mantenimientos')->nullOnDelete();
            $table->foreignId('mantenimiento_componente_id')->nullable()->constrained('mantenimiento_componentes')->nullOnDelete();
            $table->foreignId('movimiento_componente_id')->nullable()->constrained('movimientos_componentes')->nullOnDelete();
            $table->text('motivo');
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['equipo_id', 'componente_id']);
            $table->index(['deposito_id', 'destino']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retiros_granel');
    }
};
