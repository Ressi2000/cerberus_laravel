<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Departamento;
use App\Models\Cargo;
use App\Models\Empresa;

class InicialSeeder extends Seeder
{
    public function run()
    {
        $perms = ['ver usuarios','crear usuarios','editar usuarios','eliminar usuarios'];
        foreach ($perms as $p) Permission::firstOrCreate(['name'=>$p]);

        Role::firstOrCreate(['name'=>'Administrador']);
        Role::firstOrCreate(['name'=>'Analista']);
        Role::firstOrCreate(['name'=>'Usuario']);

        $empresa = Empresa::firstOrCreate(['nombre'=>'Pasta']);

        Departamento::firstOrCreate(['nombre'=>'Servicios Tecnológicos','empresa_id'=>$empresa->id]);
        Cargo::firstOrCreate(['nombre'=>'Analista de Soporte','empresa_id'=>$empresa->id]);

        $user = User::firstOrCreate(
            ['email' => 'admin@cerberus.local'],
            [
                'name' => 'Administrador Cerberus',
                'password' => bcrypt('123456789')
            ]
        );
        $user->assignRole('Administrador');
    }
}
