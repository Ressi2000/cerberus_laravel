<?php

namespace Tests\Feature\Mantenimientos;

use App\Models\CategoriaEquipo;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Mantenimiento;
use App\Models\PlanMantenimiento;
use App\Models\User;
use App\Notifications\MantenimientoProximoNotification;
use App\Notifications\PlanMantenimientoProximoNotification;
use Database\Seeders\EstadosEquiposSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Avisos de mantenimiento preventivo: 14, 7 y 1 día(s) antes, en el
 * sistema y por correo, a los analistas de la empresa.
 */
class AvisosMantenimientoTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $analista;
    private User $otraEmpresa;
    private CategoriaEquipo $laptop;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->seed(EstadosEquiposSeeder::class);

        $this->empresa = Empresa::create(['nombre' => 'Empresa A']);
        $empresaB = Empresa::create(['nombre' => 'Empresa B']);
        $this->laptop = CategoriaEquipo::create(['nombre' => 'Laptop']);

        // Analista con la empresa ASIGNADA pero otra activa: igual debe recibir el aviso.
        $this->analista = User::factory()->create(['empresa_activa_id' => $empresaB->id]);
        $this->analista->assignRole('Analista');
        $this->analista->empresasAsignadas()->attach($this->empresa->id);

        $this->otraEmpresa = User::factory()->create(['empresa_activa_id' => $empresaB->id]);
        $this->otraEmpresa->assignRole('Analista');

        foreach (range(1, 3) as $i) {
            Equipo::forceCreate([
                'empresa_id' => $this->empresa->id, 'categoria_id' => $this->laptop->id,
                'estado_id' => EstadoEquipo::where('nombre', 'Disponible')->value('id'),
                'codigo_interno' => "EQ-{$i}", 'activo' => true,
            ]);
        }
    }

    private function plan(int $enDias): PlanMantenimiento
    {
        return PlanMantenimiento::create([
            'empresa_id' => $this->empresa->id, 'categoria_id' => $this->laptop->id,
            'frecuencia_meses' => 6, 'fecha_proximo' => now()->addDays($enDias)->toDateString(),
            'activo' => true, 'creado_por' => $this->analista->id,
        ]);
    }

    private function avisar(): void
    {
        $this->artisan('cerberus:notificar-alertas')->assertSuccessful();
    }

    /** @test */
    public function el_cronograma_avisa_14_7_y_1_dia_antes_con_un_solo_aviso_por_lote(): void
    {
        Notification::fake();
        foreach ([14, 7, 1] as $dias) {
            $this->plan($dias);
        }
        $this->plan(10);   // no es día de aviso
        $this->plan(20);

        $this->avisar();

        Notification::assertSentToTimes($this->analista, PlanMantenimientoProximoNotification::class, 3);
        Notification::assertSentTo($this->analista, PlanMantenimientoProximoNotification::class,
            fn ($n, $canales) => $n->equipos === 3 && in_array('mail', $canales) && in_array('database', $canales));
        Notification::assertNotSentTo($this->otraEmpresa, PlanMantenimientoProximoNotification::class);
    }

    /** @test */
    public function un_preventivo_suelto_avisa_en_los_mismos_dias_y_no_se_repite_el_mismo_dia(): void
    {
        $caso = Mantenimiento::create([
            'empresa_id' => $this->empresa->id, 'equipo_id' => Equipo::first()->id, 'tipo' => 'Preventivo',
            'estado' => 'Programado', 'reportado_por_id' => $this->analista->id,
            'fecha_inicio' => now()->addDays(7)->toDateString(), 'proxima_fecha_programada' => now()->addDays(7)->toDateString(),
        ]);

        $this->avisar();
        $this->avisar();   // si el comando corre dos veces el mismo día, no duplica

        $avisos = $this->analista->notifications()->where('type', MantenimientoProximoNotification::class)->get();
        $this->assertCount(1, $avisos);
        $this->assertSame($caso->id, $avisos->first()->data['meta']['mantenimiento_id']);
        $this->assertStringContainsString('en 7 días', $avisos->first()->data['titulo']);

        // Al día siguiente (faltan 6) no avisa; faltando 1 dice "mañana".
        $this->travel(1)->days();
        $this->avisar();
        $this->assertSame(1, $this->analista->notifications()->where('type', MantenimientoProximoNotification::class)->count());

        $this->travel(5)->days();
        $this->avisar();
        $this->assertStringContainsString('mañana', $this->analista->notifications()->latest()->first()->data['titulo']);
    }
}
