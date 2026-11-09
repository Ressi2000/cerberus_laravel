<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla maestra — Tipos de Licencia Microsoft (Premium, Standard, Basic,
 * Outlook simple, No tiene). Control de ciclo de vida mediante `activo`,
 * igual que estados_equipos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_licencia_microsoft', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_licencia_microsoft');
    }
};
