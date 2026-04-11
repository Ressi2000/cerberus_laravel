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
       // Si la tabla ya existe, solo añadimos columnas faltantes
        if (! Schema::hasTable('departamentos')) {
            Schema::create('departamentos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 255);
                $table->text('descripcion')->nullable();
                $table->foreignId('empresa_id')
                      ->nullable()
                      ->constrained('empresas')
                      ->nullOnDelete();
                $table->softDeletes();
                $table->timestamps();
 
                $table->index('empresa_id');
                $table->index('nombre');
            });
        } else {
            Schema::table('departamentos', function (Blueprint $table) {
                // Añadir columnas que puedan faltar
                if (! Schema::hasColumn('departamentos', 'descripcion')) {
                    $table->text('descripcion')->nullable()->after('nombre');
                }
                if (! Schema::hasColumn('departamentos', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departamentos', function (Blueprint $table) {
            //
        });
    }
};
