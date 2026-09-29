<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Depósitos: lugares de guardado (equipos dados de baja + componentes
 * descartados/dañados) — distintos de Ubicación, que es donde un equipo
 * ACTIVO está siendo usado. Un depósito es siempre de una empresa; el
 * Administrador puede crear varios por empresa según la necesidad (ej.
 * "Depósito Principal", "Depósito de Tóxicos"). Puede apoyarse en una
 * Ubicación existente para decir dónde está físicamente, sin duplicar esa
 * información.
 *
 * Sin SoftDeletes — mismo criterio que CategoriaEquipo: el ciclo de vida
 * se controla con `activo`, así el unique(empresa_id, nombre) funciona
 * bien y "eliminar" no bloquea reusar el nombre más adelante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depositos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones')->nullOnDelete();

            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);

            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            $table->unique(['empresa_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depositos');
    }
};
