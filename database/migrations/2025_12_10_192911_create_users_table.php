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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('username', 50)->unique();
            $table->string('email')->unique();

            $table->string('ficha')->nullable();
            $table->string('cedula', 50);

            // Relaciones
            $table->foreignId('empresa_id')
                ->nullable()
                ->constrained('empresas')
                ->nullOnDelete();

            $table->foreignId('departamento_id')
                ->nullable()
                ->constrained('departamentos')
                ->nullOnDelete();

            $table->foreignId('cargo_id')
                ->nullable()
                ->constrained('cargos')
                ->nullOnDelete();

            $table->foreignId('ubicacion_id')
                ->nullable()
                ->comment('Empresa donde está fisicamente laborando el usuario')
                ->constrained('empresas')
                ->nullOnDelete();

            $table->string('telefono')->nullable();

            // Jefe (relación recursiva)
            $table->foreignId('jefe_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('foto')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
