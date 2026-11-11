<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Familias de piezas (RAM, Discos…): la ficha técnica de una pieza se define
 * UNA vez en su familia y la comparten todas las categorías de equipos.
 *
 * - campos: JSON con la ficha [{id, nombre, tipo, opciones, requerido, debe_coincidir}].
 *   Mismo formato que atributos_equipos.sub_campos, más "debe_coincidir"
 *   (campos que deben ser iguales para que una pieza encaje en un equipo).
 * - control: "unidad" (cada pieza con su serial) o "granel" (solo cantidad).
 *
 * Una característica de una categoría que "usa" una familia
 * (atributos_equipos.familia_id) toma sus campos de aquí, ver
 * AtributoEquipo::subCampos().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('familias_componentes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->enum('control', ['unidad', 'granel'])->default('unidad');
            $table->json('campos');
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('activo');
        });

        Schema::table('atributos_equipos', function (Blueprint $table) {
            $table->foreignId('familia_id')->nullable()->after('categoria_id')
                ->constrained('familias_componentes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('atributos_equipos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('familia_id');
        });

        Schema::dropIfExists('familias_componentes');
    }
};
