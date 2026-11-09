<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $user = User::firstOrCreate(
            ['email' => 'ressi2000w@hotmail.com'],
            [
                'name' => 'Ressi Figuera',
                // Empresa de nómina (obligatoria): la primera que exista.
                'empresa_id' => Empresa::query()->value('id')
                    ?? Empresa::firstOrCreate(['nombre' => 'Pasta'])->id,
                'username' => 'rfiguera',
                'cedula' => '27646169',
                'password' => Hash::make('123456789'), // cámbialo luego
                'estado' => 'Activo',
            ]
        );

        // Asignar rol administrador (ID = 1)
        $user->assignRole(1);
    }
}
