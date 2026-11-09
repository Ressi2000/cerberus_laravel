<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('componentes_almacen', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('unidad', 30)->default('unidad');
            $table->unsignedInteger('stock_actual')->default(0);
            $table->boolean('activo')->default(true);

            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('empresa_id');
            $table->index(['empresa_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('componentes_almacen');
    }
};
