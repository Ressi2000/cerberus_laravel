<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traslado_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('traslado_id')->constrained('traslados')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traslado_items');
    }
};
