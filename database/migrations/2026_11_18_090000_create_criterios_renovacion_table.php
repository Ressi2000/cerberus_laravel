<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Criterios para clasificar los equipos (sano / por revisar / repotenciable /
 * obsoleto), por categoría. Si una categoría no tiene fila, se usan los
 * valores sugeridos (ver CriterioRenovacion::VALORES_SUGERIDOS).
 *
 * atributo_*_id: de qué característica de la ficha sale cada dato. Si son
 * NULL se detectan solas (la RAM y los discos por su familia, el procesador
 * y el sistema operativo por el nombre de la característica).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criterios_renovacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->unique()->constrained('categorias_equipos')->cascadeOnDelete();
            $table->boolean('evaluar')->default(true);

            $table->foreignId('atributo_procesador_id')->nullable()->constrained('atributos_equipos')->nullOnDelete();
            $table->foreignId('atributo_ram_id')->nullable()->constrained('atributos_equipos')->nullOnDelete();
            $table->foreignId('atributo_disco_id')->nullable()->constrained('atributos_equipos')->nullOnDelete();
            $table->foreignId('atributo_so_id')->nullable()->constrained('atributos_equipos')->nullOnDelete();

            $table->unsignedTinyInteger('edad_revisar');          // años
            $table->unsignedTinyInteger('edad_obsoleto');         // años
            $table->unsignedTinyInteger('intel_generacion_minima');
            $table->unsignedTinyInteger('ryzen_serie_minima');
            $table->string('gama_basica', 10);                    // ignorar | revisar | obsoleto
            $table->unsignedSmallInteger('ram_minima_gb');
            $table->json('ram_tipos_obsoletos');
            $table->boolean('requiere_ssd');
            $table->unsignedInteger('disco_minimo_gb');
            $table->unsignedTinyInteger('reparaciones_max_12m');
            $table->json('so_sin_soporte');

            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterios_renovacion');
    }
};
