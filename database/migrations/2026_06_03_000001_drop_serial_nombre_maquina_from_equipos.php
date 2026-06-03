<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn(['serial', 'nombre_maquina']);
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('serial')->nullable()->unique()->after('codigo_interno');
            $table->string('nombre_maquina')->nullable()->after('serial');
        });
    }
};
