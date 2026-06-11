<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('traslados', function (Blueprint $table) {
            $table->enum('estado', ['activo', 'revertido'])->default('activo')->after('observaciones');
            $table->text('motivo_reversion')->nullable()->after('estado');
            $table->foreignId('revertido_por_id')->nullable()->constrained('users')->nullOnDelete()->after('motivo_reversion');
            $table->timestamp('revertido_at')->nullable()->after('revertido_por_id');
        });
    }

    public function down(): void
    {
        Schema::table('traslados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revertido_por_id');
            $table->dropColumn(['estado', 'motivo_reversion', 'revertido_at']);
        });
    }
};
