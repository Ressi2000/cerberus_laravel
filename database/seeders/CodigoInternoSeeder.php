<?php

namespace Database\Seeders;

use App\Models\Equipo;
use App\Services\CodigoInternoService;
use Illuminate\Database\Seeder;

class CodigoInternoSeeder extends Seeder
{
    public function run(CodigoInternoService $codigos): void
    {
        // Solo equipos que aún no tienen código (por si se corre más de una vez)
        Equipo::withTrashed()
            ->whereNull('codigo_interno')
            ->orderBy('id')
            ->each(function (Equipo $equipo) use ($codigos) {
                $equipo->updateQuietly([
                    'codigo_interno' => $codigos->generar($equipo->id),
                ]);
            });

        $this->command->info('Códigos internos generados correctamente.');
    }
}