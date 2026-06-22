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
        Schema::create('firmas', function (Blueprint $table) {
            $table->id();

            // Documento firmado: Asignacion, Prestamo o Traslado.
            $table->string('firmable_type');
            $table->unsignedBigInteger('firmable_id');

            // 'receptor' o 'jefe' (en traslado, 'jefe' = quien autoriza).
            $table->string('rol');

            // Usuario que debe/firmó — se conoce desde que se solicita la firma.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('estado')->default('pendiente'); // pendiente | firmado
            $table->text('imagen')->nullable(); // PNG base64 del trazo
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('firmado_at')->nullable();

            $table->timestamps();

            $table->index(['firmable_type', 'firmable_id']);
            $table->unique(['firmable_type', 'firmable_id', 'rol']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firmas');
    }
};
