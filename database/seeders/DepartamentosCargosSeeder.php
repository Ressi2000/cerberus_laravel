<?php

namespace Database\Seeders;

use App\Models\Cargo;
use App\Models\Departamento;
use App\Models\Empresa;
use Illuminate\Database\Seeder;

/**
 * Seeder de Departamentos y Cargos
 *
 * empresa_id = NULL  → global (existe en todas las empresas)
 * empresa_id = X     → exclusivo de esa empresa
 *
 * Estructura:
 *   Departamento → Cargos del departamento
 */
class DepartamentosCargosSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar datos previos (orden inverso por FK)
        Cargo::query()->delete();
        Departamento::query()->delete();

        // ── Departamentos GLOBALES (empresa_id = NULL) ────────────────────────
        $depGlobales = [
            'Recursos Humanos',
            'Administración',
            'Finanzas',
            'Operaciones',
            'Servicios Tecnológicos',
            'Seguridad',
            'Legal',
        ];

        foreach ($depGlobales as $nombre) {
            Departamento::create([
                'nombre'     => $nombre,
                'empresa_id' => null, // global
            ]);
        }

        // ── Departamentos EXCLUSIVOS (requieren una empresa) ──────────────────
        // Ajusta los nombres de empresas según tus datos reales
        $empresa1 = Empresa::where('nombre', 'like', '%Pasta%')->first()
            ?? Empresa::first();

        if ($empresa1) {
            Departamento::create([
                'nombre'     => 'Aplicaciones',
                'empresa_id' => $empresa1->id,
            ]);
            Departamento::create([
                'nombre'     => 'Infraestructura',
                'empresa_id' => $empresa1->id,
            ]);
        }

        // ── Cargos por Departamento ───────────────────────────────────────────
        $this->seedCargos();
    }

    private function seedCargos(): void
    {
        // Mapa: nombre de departamento → array de cargos
        // Los cargos heredan empresa_id del departamento (null = global)
        $mapa = [
            'Recursos Humanos' => [
                'Gerente de RRHH',
                'Analista de Nómina',
                'Analista de Reclutamiento',
                'Coordinador de Bienestar',
            ],
            'Administración' => [
                'Gerente Administrativo',
                'Asistente Administrativo',
                'Coordinador Administrativo',
            ],
            'Finanzas' => [
                'Gerente de Finanzas',
                'Analista Financiero',
                'Contador',
                'Auxiliar Contable',
            ],
            'Operaciones' => [
                'Gerente de Operaciones',
                'Supervisor de Operaciones',
                'Operador',
            ],
            'Servicios Tecnológicos' => [
                'Gerente de TI',
                'Analista de Soporte',
                'Técnico de Soporte',
                'Coordinador TI',
            ],
            'Seguridad' => [
                'Jefe de Seguridad',
                'Analista de Seguridad',
                'Supervisor de Seguridad',
            ],
            'Legal' => [
                'Gerente Legal',
                'Abogado',
                'Asistente Legal',
            ],
            'Aplicaciones' => [
                'Líder de Desarrollo',
                'Desarrollador Backend',
                'Desarrollador Frontend',
                'QA Engineer',
            ],
            'Infraestructura' => [
                'Administrador de Sistemas',
                'Administrador de Redes',
                'Ingeniero de Infraestructura',
            ],
        ];

        foreach ($mapa as $depNombre => $cargos) {
            $dep = Departamento::where('nombre', $depNombre)->first();

            if (! $dep) continue;

            foreach ($cargos as $cargo) {
                Cargo::create([
                    'nombre'          => $cargo,
                    'empresa_id'      => $dep->empresa_id,   // hereda global/exclusivo
                    'departamento_id' => $dep->id,
                ]);
            }
        }
    }
}
