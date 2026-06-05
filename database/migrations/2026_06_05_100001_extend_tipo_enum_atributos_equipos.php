<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extiende los valores permitidos del campo `tipo` en atributos_equipos
 * añadiendo: 'select', 'file', 'group'.
 *
 * SQLite no soporta ALTER TABLE ... MODIFY COLUMN, por lo que se recrea
 * la tabla usando la estrategia rename + create + copy + drop.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Crear tabla temporal con el constraint ampliado
        DB::statement('CREATE TABLE "atributos_equipos_new" (
            "id" integer primary key autoincrement not null,
            "categoria_id" integer not null,
            "nombre" varchar not null,
            "slug" varchar not null,
            "tipo" varchar check ("tipo" in (
                \'string\', \'integer\', \'decimal\', \'boolean\',
                \'date\', \'text\', \'select\', \'file\', \'group\'
            )) not null,
            "requerido" tinyint(1) not null default \'0\',
            "filtrable" tinyint(1) not null default \'0\',
            "visible_en_tabla" tinyint(1) not null default \'1\',
            "orden" integer not null default \'0\',
            "created_at" datetime,
            "updated_at" datetime,
            "opciones" text,
            "sub_campos" text,
            "activo" tinyint(1) not null default \'1\',
            foreign key("categoria_id") references "categorias_equipos"("id") on delete cascade
        )');

        // Copiar datos
        DB::statement('INSERT INTO "atributos_equipos_new" SELECT * FROM "atributos_equipos"');

        // Eliminar tabla original y renombrar
        Schema::drop('atributos_equipos');
        DB::statement('ALTER TABLE "atributos_equipos_new" RENAME TO "atributos_equipos"');

        // Recrear índice único
        DB::statement('CREATE UNIQUE INDEX "atributos_equipos_categoria_id_slug_unique" ON "atributos_equipos" ("categoria_id", "slug")');
    }

    public function down(): void
    {
        // No revertir — quitaría tipos válidos de filas existentes
    }
};
