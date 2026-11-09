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
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            // SQLite (no así MySQL) exige borrar el índice simple explícitamente
            // antes de borrar la columna que indexa, o falla el ALTER completo.
            $table->dropIndex(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->constrained();
        });
    }
};
