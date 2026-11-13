<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();

            // Multiempresa
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();

            // Clasificación
            $table->foreignId('categoria_id')->constrained('categorias_equipos');
            $table->foreignId('estado_id')->constrained('estados_equipos');
            $table->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones');

            // Identificación técnica. Marca y modelo viven como atributos
            // (EAV) de cada categoría, no como columnas — igual que producción.
            $table->string('serial')->nullable()->unique();
            $table->string('nombre_maquina')->nullable(); // hostname
            $table->string('codigo_interno')->unique();   // QR o código único interno

            // Ciclo de vida
            $table->date('fecha_adquisicion')->nullable();
            $table->date('fecha_garantia_fin')->nullable();

            // Estado lógico adicional
            $table->boolean('activo')->default(true);

            // Notas técnicas
            $table->text('observaciones')->nullable();

            // creado_por lo agrega 2026_03_21_170022_add_equipos_field (con
            // nullOnDelete), igual que en producción.

            $table->timestamps();
            $table->softDeletes();

            // Índices críticos
            $table->index(['empresa_id', 'estado_id']);
            $table->index('serial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
