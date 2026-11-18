<?php

namespace Tests\Feature\Equipos;

use App\Livewire\Equipos\CriteriosRenovacionModal;
use App\Livewire\Equipos\RenovacionEquipos;
use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\CriterioRenovacion;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EquipoAtributoGrupoInstancia;
use App\Models\EquipoAtributoValor;
use App\Models\EstadoEquipo;
use App\Models\FamiliaComponente;
use App\Models\Mantenimiento;
use App\Models\User;
use App\Services\RenovacionService;
use App\Services\VinculacionFamiliaService;
use App\Support\ProcesadorInfo;
use App\Support\SistemaOperativoInfo;
use Database\Seeders\EstadosEquiposSeeder;
use Database\Seeders\FamiliasComponentesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Renovación de equipos: reglas explicables por tipo de equipo.
 */
class RenovacionEquiposTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $admin;
    private CategoriaEquipo $laptop;
    private AtributoEquipo $cpu;
    private AtributoEquipo $so;
    private AtributoEquipo $ram;
    private AtributoEquipo $disco;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->seed(EstadosEquiposSeeder::class);
        $this->seed(FamiliasComponentesSeeder::class);

        $this->empresa = Empresa::create(['nombre' => 'Empresa A']);
        $this->admin   = User::factory()->create(['empresa_id' => $this->empresa->id]);
        $this->admin->assignRole('Administrador');

        $this->laptop = CategoriaEquipo::create(['nombre' => 'Laptop']);
        $this->cpu    = AtributoEquipo::create(['categoria_id' => $this->laptop->id, 'nombre' => 'Procesador', 'slug' => 'procesador', 'tipo' => 'string']);
        $this->so     = AtributoEquipo::create(['categoria_id' => $this->laptop->id, 'nombre' => 'Sistema Operativo', 'slug' => 'so', 'tipo' => 'string']);
        $this->ram    = AtributoEquipo::create(['categoria_id' => $this->laptop->id, 'nombre' => 'RAM', 'slug' => 'ram', 'tipo' => 'group', 'reutilizable' => true]);
        $this->disco  = AtributoEquipo::create(['categoria_id' => $this->laptop->id, 'nombre' => 'Disco Duro', 'slug' => 'disco', 'tipo' => 'group', 'reutilizable' => true]);
        app(VinculacionFamiliaService::class)->vincularSinDatos($this->ram, FamiliaComponente::where('nombre', 'RAM')->first());
        app(VinculacionFamiliaService::class)->vincularSinDatos($this->disco, FamiliaComponente::where('nombre', 'Discos')->first());
    }

    private function campo(AtributoEquipo $a, string $nombre): string
    {
        return collect($a->fresh()->sub_campos)->firstWhere('nombre', $nombre)['id'];
    }

    /** Equipo con su ficha: procesador, SO, RAM [[gb, tipo]…], discos [[gb, tipo]…]. */
    private function equipo(string $codigo, ?string $cpu, ?string $so, array $ram = [], array $discos = [], ?string $fecha = null): Equipo
    {
        $e = Equipo::forceCreate([
            'empresa_id' => $this->empresa->id, 'categoria_id' => $this->laptop->id,
            'estado_id' => EstadoEquipo::where('nombre', 'Disponible')->value('id'),
            'codigo_interno' => $codigo, 'activo' => true, 'fecha_adquisicion' => $fecha,
        ]);
        foreach ([[$this->cpu, $cpu], [$this->so, $so]] as [$a, $v]) {
            if ($v !== null) {
                EquipoAtributoValor::create(['equipo_id' => $e->id, 'atributo_id' => $a->id, 'valor' => $v, 'es_actual' => true]);
            }
        }
        foreach ($ram as $i => [$gb, $tipo]) {
            EquipoAtributoGrupoInstancia::create(['equipo_id' => $e->id, 'atributo_id' => $this->ram->id, 'orden' => $i, 'es_actual' => true, 'creado_por' => $this->admin->id,
                'valores' => [$this->campo($this->ram, 'Capacidad (GB)') => $gb, $this->campo($this->ram, 'Tipo') => $tipo]]);
        }
        foreach ($discos as $i => [$gb, $tipo]) {
            EquipoAtributoGrupoInstancia::create(['equipo_id' => $e->id, 'atributo_id' => $this->disco->id, 'orden' => $i, 'es_actual' => true, 'creado_por' => $this->admin->id,
                'valores' => [$this->campo($this->disco, 'Capacidad (GB)') => $gb, $this->campo($this->disco, 'Tipo') => $tipo]]);
        }

        return $e;
    }

    private function evaluar(Equipo $e): array
    {
        return app(RenovacionService::class)->evaluarEquipos(collect([$e->fresh()]))->first()['resultado'];
    }

    // ─── Lectores ──────────────────────────────────────────────────────────

    /** @test */
    public function lee_procesadores_y_sistemas_escritos_de_muchas_formas(): void
    {
        $casos = [
            'i5-1135G7 @ 2.40GHz' => ['core', 11], 'Intel Core i3 2328M 2.20GHz' => ['core', 2], 'i7-1255U, 2611MHz' => ['core', 12],
            'Intel Core i5-10210U' => ['core', 10], 'I5-2450M 2.50 GHZ' => ['core', 2], 'Ultra 7 155U' => ['ultra', 15],
            'AMD Ryzen 5 3500U' => ['ryzen', 3], 'Pentium(R) CPU G645T' => ['gama_basica', null], 'AMD A8-6410' => ['antiguo', null],
            'Core 2 Duo E8400' => ['antiguo', null], 'Intel Xeon E5-2620' => ['servidor', null], 'sss' => [null, null],
        ];
        foreach ($casos as $texto => [$linea, $gen]) {
            $p = ProcesadorInfo::desde($texto);
            $this->assertSame($linea, $p->linea, $texto);
            $this->assertSame($gen, $p->generacion, $texto);
        }

        $this->assertSame('10', SistemaOperativoInfo::desde('W10')->windows);
        $this->assertSame('8.1', SistemaOperativoInfo::desde('Win 8.1 Pro x64bits')->windows);
        $this->assertSame('11', SistemaOperativoInfo::desde('Windows 11 Pro x 64Bits')->windows);
        $this->assertNull(SistemaOperativoInfo::desde('Ubuntu 22.04')->windows);
    }

    // ─── Clasificación ─────────────────────────────────────────────────────

    /** @test */
    public function clasifica_cada_equipo_con_su_motivo(): void
    {
        $sano = $this->equipo('SANO', 'i5-1235U', 'Windows 11 Pro', [['16', 'DDR4']], [['512', 'M.2 NVMe']], now()->subYear()->toDateString());
        $repo = $this->equipo('REPO', 'i5-8250U', 'W10', [['4', 'DDR4']], [['500', 'HDD 2.5"']], now()->subYears(3)->toDateString());
        $viejo = $this->equipo('VIEJO', 'i3-3110M', 'Win 7', [['4', 'DDR3']], [['320', 'HDD 2.5"']]);
        $ddr2 = $this->equipo('DDR2', 'i5-8250U', 'Windows 11', [['2', 'DDR2']], [['256', 'SSD 2.5" SATA']], now()->subYears(2)->toDateString());

        $r = $this->evaluar($sano);
        $this->assertSame('sano', $r['clasificacion']);

        $r = $this->evaluar($repo);
        $this->assertSame('repotenciable', $r['clasificacion']);
        $this->assertStringContainsString('Subir la RAM de 4 GB a 8 GB', implode(' ', $r['mejoras']));
        $this->assertStringContainsString('SSD', implode(' ', $r['mejoras']));
        $this->assertStringContainsString('actualizar a Windows 11', implode(' ', $r['mejoras']));

        $r = $this->evaluar($viejo);
        $this->assertSame('obsoleto', $r['clasificacion']);
        $this->assertStringContainsString('no cumple los requisitos de Windows 11', implode(' ', $r['obsoleto']));
        $this->assertSame(['Falta la fecha de adquisición.'] === $r['faltan'], false, 'Edad estimada por el procesador: no falta.');
        $this->assertTrue($r['datos']['edad_estimada']);

        $r = $this->evaluar($ddr2);
        $this->assertSame('obsoleto', $r['clasificacion']);
        $this->assertStringContainsString('DDR2', implode(' ', $r['obsoleto']));
    }

    /** @test */
    public function la_edad_estimada_solo_marca_por_revisar_y_la_real_puede_volverlo_obsoleto(): void
    {
        $estimada = $this->equipo('EST', 'i5-8250U', 'Windows 11', [['16', 'DDR4']], [['512', 'M.2 NVMe']]);          // 2017, sin fecha
        $real     = $this->equipo('REAL', 'i5-8250U', 'Windows 11', [['16', 'DDR4']], [['512', 'M.2 NVMe']], now()->subYears(9)->toDateString());

        $this->assertSame('revisar', $this->evaluar($estimada)['clasificacion']);
        $this->assertSame('obsoleto', $this->evaluar($real)['clasificacion']);
    }

    /** @test */
    public function muchas_reparaciones_en_un_anio_lo_vuelven_obsoleto(): void
    {
        $e = $this->equipo('REP', 'i5-1235U', 'Windows 11', [['16', 'DDR4']], [['512', 'M.2 NVMe']], now()->subYear()->toDateString());
        foreach (range(1, 3) as $i) {
            Mantenimiento::create(['empresa_id' => $this->empresa->id, 'equipo_id' => $e->id, 'tipo' => 'Correctivo', 'estado' => 'Cerrado',
                'reportado_por_id' => $this->admin->id, 'fecha_inicio' => now()->subMonths($i)->toDateString()]);
        }

        $r = $this->evaluar($e);
        $this->assertSame('obsoleto', $r['clasificacion']);
        $this->assertStringContainsString('3 reparaciones', implode(' ', $r['obsoleto']));
    }

    /** @test */
    public function una_ficha_incompleta_lo_dice(): void
    {
        $r = $this->evaluar($this->equipo('VACIO', 'sss', null));

        $this->assertSame('sin_datos', $r['clasificacion']);
        $this->assertStringContainsString('No se reconoce el procesador «sss»', implode(' ', $r['faltan']));
        $this->assertStringContainsString('Falta la RAM', implode(' ', $r['faltan']));
    }

    // ─── Criterios ─────────────────────────────────────────────────────────

    /** @test */
    public function los_criterios_se_ajustan_por_tipo_de_equipo(): void
    {
        $e = $this->equipo('GEN7', 'i7-7700HQ', 'Windows 11', [['16', 'DDR4']], [['512', 'M.2 NVMe']], now()->subYears(2)->toDateString());
        $this->assertSame('obsoleto', $this->evaluar($e)['clasificacion']);

        Livewire::actingAs($this->admin)->test(CriteriosRenovacionModal::class)
            ->call('abrir', $this->laptop->id)
            ->assertSet('f.intel_generacion_minima', 8)
            ->set('f.intel_generacion_minima', 7)
            ->set('f.edad_obsoleto', 2)->set('f.edad_revisar', 5)
            ->call('guardar')->assertHasErrors('f.edad_obsoleto')
            ->set('f.edad_obsoleto', 8)
            ->call('guardar')->assertHasNoErrors()
            ->assertDispatched('criteriosRenovacionGuardados');

        $this->assertSame(7, CriterioRenovacion::where('categoria_id', $this->laptop->id)->value('intel_generacion_minima'));
        $this->assertSame('sano', $this->evaluar($e)['clasificacion']);
    }

    /** @test */
    public function solo_el_administrador_cambia_los_criterios(): void
    {
        $analista = User::factory()->create(['empresa_id' => $this->empresa->id, 'empresa_activa_id' => $this->empresa->id]);
        $analista->assignRole('Analista');

        Livewire::actingAs($analista)->test(CriteriosRenovacionModal::class)
            ->call('abrir', $this->laptop->id)
            ->assertForbidden();
    }

    // ─── Pantalla y Excel ──────────────────────────────────────────────────

    /** @test */
    public function la_pantalla_lista_cuenta_y_filtra(): void
    {
        $this->equipo('SANO-1', 'i5-1235U', 'Windows 11 Pro', [['16', 'DDR4']], [['512', 'M.2 NVMe']], now()->subYear()->toDateString());
        $this->equipo('VIEJO-1', 'i3-3110M', 'Win 7', [['4', 'DDR3']], [['320', 'HDD 2.5"']]);

        Livewire::actingAs($this->admin)->test(RenovacionEquipos::class)
            ->assertSee('SANO-1')->assertSee('VIEJO-1')
            ->assertSet('totales.obsoleto', 1)
            ->call('verClasificacion', 'obsoleto')
            ->assertSee('VIEJO-1')->assertDontSee('SANO-1')
            ->assertSee('Por qué conviene reemplazarlo');

        $this->actingAs($this->admin)->get(route('admin.renovacion.index'))->assertOk()->assertSee('Renovación de equipos');
        $this->actingAs($this->admin)->get(route('admin.renovacion.exportar'))
            ->assertOk()->assertDownload();
    }
}
