<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Duplicado muerto de create_equipos_table (2026_02_18_141629), con un
     * subconjunto de columnas de esa misma tabla y sin ningún drop entre
     * medio — en un migrate desde cero fallaba con "table already exists",
     * mismo bug ya visto en create_users_table/create_cargos_table/
     * create_departamentos_table. Acá no hay ninguna columna extra que
     * rescatar (la versión 141629 ya las tiene todas), así que queda vacía
     * en vez de convertirse en alter.
     */
    public function up(): void
    {
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
