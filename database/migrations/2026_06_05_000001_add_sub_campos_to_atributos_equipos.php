<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atributos_equipos', function (Blueprint $table) {
            // sub_campos solo aplica cuando tipo = 'group'
            // JSON: [{"id":"uuid","nombre":"Tipo","tipo":"select","opciones":["HDD","SSD"],"requerido":true}]
            $table->json('sub_campos')->nullable()->after('opciones');
        });
    }

    public function down(): void
    {
        Schema::table('atributos_equipos', function (Blueprint $table) {
            $table->dropColumn('sub_campos');
        });
    }
};
