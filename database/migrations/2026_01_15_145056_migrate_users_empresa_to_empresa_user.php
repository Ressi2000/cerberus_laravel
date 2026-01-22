<?php

use Illuminate\Support\Facades\DB;
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
        Schema::table('empresa_user', function (Blueprint $table) {
            DB::table('users')
            ->whereNotNull('empresa_id')
            ->orderBy('id')
            ->chunk(100, function ($users) {

                foreach ($users as $user) {
                    // Insertar relación empresa_user
                    DB::table('empresa_user')->insertOrIgnore([
                        'user_id'       => $user->id,
                        'empresa_id'    => $user->empresa_id,
                        'es_principal'  => true,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);

                    // Definir empresa activa
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'empresa_activa_id' => $user->empresa_id
                        ]);
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('empresa_user')->truncate();

        DB::table('users')->update([
            'empresa_activa_id' => null
        ]);
    }
};
