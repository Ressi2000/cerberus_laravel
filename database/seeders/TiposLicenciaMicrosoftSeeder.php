<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TiposLicenciaMicrosoftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            'Premium',
            'Standard',
            'Basic',
            'Outlook simple',
            'No tiene',
        ];

        foreach ($tipos as $tipo) {
            \App\Models\TipoLicenciaMicrosoft::firstOrCreate([
                'nombre' => $tipo,
            ]);
        }
    }
}
