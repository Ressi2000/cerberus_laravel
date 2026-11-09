<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tipo_licencia_microsoft_id')
                ->nullable()
                ->after('cargo_id')
                ->constrained('tipos_licencia_microsoft')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tipo_licencia_microsoft_id']);
            $table->dropColumn('tipo_licencia_microsoft_id');
        });
    }
};
