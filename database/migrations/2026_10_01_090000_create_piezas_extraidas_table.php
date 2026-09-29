<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Piezas extraídas: identidad individual de cada pieza física (RAM, disco,
 * etc.) que sale de un equipo — ya sea porque el equipo se dio de baja
 * (obsolescencia) o porque se sustituyó durante una reparación.
 *
 * No confundir con `componentes_almacen`, que es stock agregado por
 * nombre/empresa (cantidad, sin identidad individual). Una pieza reutilizable
 * SUMA una unidad a su `ComponenteAlmacen` correspondiente (componente_almacen_id)
 * pero además queda registrada aquí con su propia trazabilidad: de qué equipo
 * salió, en qué atributo (y qué instancia, si el atributo es tipo 'group'),
 * y hacia dónde fue después (Almacén, Depósito, u otro equipo).
 *
 * `valor_extraido` es una fotografía del valor en el momento de la extracción,
 * porque el EquipoAtributoValor/EquipoAtributoGrupoInstancia original pasa a
 * es_actual = false y no debe alterar lo que esta pieza "es".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piezas_extraidas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();

            // Origen: de qué equipo y qué atributo salió la pieza.
            $table->foreignId('equipo_origen_id')->constrained('equipos')->restrictOnDelete();
            $table->foreignId('atributo_id')->constrained('atributos_equipos')->restrictOnDelete();
            $table->foreignId('grupo_instancia_id')->nullable()
                ->constrained('equipo_atributo_grupo_instancias')->nullOnDelete();
            $table->json('valor_extraido');
            $table->boolean('reutilizable');

            // Estado actual del ciclo de vida de la pieza.
            $table->string('estado', 20)->default('en_almacen');
            // en_almacen | instalada | en_deposito

            // Destino actual (solo uno de los tres aplica según `estado`).
            $table->foreignId('componente_almacen_id')->nullable()
                ->constrained('componentes_almacen')->nullOnDelete();
            $table->foreignId('deposito_id')->nullable()
                ->constrained('depositos')->nullOnDelete();
            $table->foreignId('equipo_destino_id')->nullable()
                ->constrained('equipos')->nullOnDelete();

            // Contexto de la extracción.
            $table->foreignId('mantenimiento_id')->nullable()
                ->constrained('mantenimientos')->nullOnDelete();
            $table->string('motivo', 30);
            // baja_equipo | sustitucion_reparacion

            $table->foreignId('extraido_por')->constrained('users')->restrictOnDelete();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index('equipo_origen_id');
            $table->index('estado');
            $table->index(['empresa_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piezas_extraidas');
    }
};
