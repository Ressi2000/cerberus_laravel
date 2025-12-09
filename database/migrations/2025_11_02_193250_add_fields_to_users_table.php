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
            $table->unsignedBigInteger('empresa_id')->nullable()->after('id');
            $table->unsignedBigInteger('departamento_id')->nullable()->after('empresa_id');
            $table->unsignedBigInteger('cargo_id')->nullable()->after('departamento_id');
            $table->unsignedBigInteger('ubicacion_id')->nullable()->after('cargo_id');
            $table->boolean('estado')->default(true)->after('ubicacion_id');
            $table->string('telefono')->nullable()->after('estado');
            $table->string('foto')->nullable()->after('telefono');
            $table->string('ficha')->nullable()->after('foto');

            $table->index('empresa_id');
            $table->index('departamento_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
