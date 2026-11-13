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
        // Sin efecto a propósito: el modelo Ubicacion no usa SoftDeletes y
        // producción no tiene ubicaciones.deleted_at. Se deja el archivo
        // porque las bases existentes lo tienen registrado como ejecutado.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            //
        });
    }
};
