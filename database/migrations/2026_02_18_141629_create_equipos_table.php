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

            // Identificación técnica
            $table->string('marca');
            $table->string('modelo');
            $table->string('serial')->nullable()->unique();
            $table->string('nombre_maquina')->nullable(); // hostname
            $table->string('codigo_interno')->unique();   // QR o código único interno

            // Ciclo de vida
            $table->date('fecha_adquisicion');
            $table->integer('vida_util')->nullable(); // años
            $table->date('fecha_garantia_fin')->nullable();

            // Control financiero (opcional futuro)
            $table->decimal('costo_adquisicion', 12, 2)->nullable();

            // Estado lógico adicional
            $table->boolean('activo')->default(true);

            // Notas técnicas
            $table->text('observaciones')->nullable();

            // Auditoría técnica adicional
            $table->foreignId('creado_por')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Índices críticos
            $table->index(['empresa_id', 'estado_id']);
            $table->index(['empresa_id', 'categoria_id']);
            $table->index('codigo_interno');
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
