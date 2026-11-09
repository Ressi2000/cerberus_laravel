<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `imagen` guarda el PNG base64 del trazo capturado en el canvas de firma
 * remota. Como `text` (65,535 bytes en MySQL) se queda corto en pantallas
 * de alta densidad (móviles), truncando el guardado con "Data too long for
 * column 'imagen'". Se amplía a `longtext`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firmas', function (Blueprint $table) {
            $table->longText('imagen')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('firmas', function (Blueprint $table) {
            $table->text('imagen')->nullable()->change();
        });
    }
};
