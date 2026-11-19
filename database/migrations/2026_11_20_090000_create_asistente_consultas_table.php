<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de Cerberus Bot: qué se preguntó, qué consultas usó la IA y si
 * pudo responder. Sirve para revisar en QA cómo se comporta y para saber
 * qué preguntas faltan por cubrir (sin_respuesta).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistente_consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->text('pregunta');
            $table->longText('respuesta')->nullable();
            $table->json('herramientas')->nullable();
            $table->boolean('sin_respuesta')->default(false);
            $table->string('error', 500)->nullable();
            $table->string('modelo', 60)->nullable();
            $table->unsignedInteger('duracion_ms')->nullable();
            $table->timestamps();

            $table->index(['sin_respuesta', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistente_consultas');
    }
};
