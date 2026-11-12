<?php

namespace Tests\Feature\Configuracion;

use App\Livewire\Configuracion\Atributos\VincularFamiliaModal;
use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EquipoAtributoGrupoInstancia;
use App\Models\EstadoEquipo;
use App\Models\FamiliaComponente;
use App\Models\Mantenimiento;
use App\Models\PiezaExtraida;
use App\Models\User;
use App\Services\VinculacionFamiliaService;
use Database\Seeders\EstadosEquiposSeeder;
use Database\Seeders\FamiliasComponentesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Familia Discos: vincular los discos de Laptop y Desktop (que hoy guardan
 * tipos distintos, "NVME" vs "M.2 Nvme") eligiendo equivalencias, y los
 * datos de ejemplo del almacén.
 */
class FamiliaDiscosTest extends TestCase
{
    use RefreshDatabase;

    // Sub-campos como están hoy en producción.
    private const CANTIDAD_DSK  = 'dddd0000-0000-0000-0000-000000000001';
    private const TIPO_DSK      = 'dddd0000-0000-0000-0000-000000000002';
    private const CAPACIDAD_LAP = 'eeee0000-0000-0000-0000-000000000001';
    private const TIPO_LAP      = 'eeee0000-0000-0000-0000-000000000002';

    private User $admin;
    private Empresa $empresa;
    private FamiliaComponente $discos;
    private AtributoEquipo $discoDesktop;
    private AtributoEquipo $discoLaptop;

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

        $this->discos = FamiliaComponente::where('nombre', 'Discos')->firstOrFail();

        $desktop = CategoriaEquipo::create(['nombre' => 'Desktop']);
        $laptop  = CategoriaEquipo::create(['nombre' => 'Laptop']);

        $this->discoDesktop = AtributoEquipo::create([
            'categoria_id' => $desktop->id, 'nombre' => 'Disco (GB)', 'slug' => 'disco-gb', 'tipo' => 'group', 'reutilizable' => true,
            'sub_campos' => [
                ['id' => self::CANTIDAD_DSK, 'nombre' => 'Cantidad', 'tipo' => 'string', 'opciones' => [], 'requerido' => false],
                ['id' => self::TIPO_DSK, 'nombre' => 'Tipo', 'tipo' => 'select', 'opciones' => ['HDD 2.5', 'HDD 3.5', 'SSD Sata', 'M.2 Msata', 'M.2 Nvme'], 'requerido' => false],
            ],
        ]);
        $this->discoLaptop = AtributoEquipo::create([
            'categoria_id' => $laptop->id, 'nombre' => 'Disco Duro', 'slug' => 'disco-duro', 'tipo' => 'group', 'reutilizable' => true,
            'sub_campos' => [
                ['id' => self::CAPACIDAD_LAP, 'nombre' => 'Capacidad (GB)', 'tipo' => 'string', 'opciones' => [], 'requerido' => false],
                ['id' => self::TIPO_LAP, 'nombre' => 'Tipo', 'tipo' => 'select', 'opciones' => ['HDD', 'SSD Sata', 'NVME', 'MSATA'], 'requerido' => false],
            ],
        ]);
    }

    private function campo(string $nombre): string
    {
        return collect($this->discos->campos)->firstWhere('nombre', $nombre)['id'];
    }

    private function ponerDisco(AtributoEquipo $atributo, array $valores): EquipoAtributoGrupoInstancia
    {
        $equipo = Equipo::forceCreate([
            'empresa_id' => $this->empresa->id, 'categoria_id' => $atributo->categoria_id,
            'estado_id' => EstadoEquipo::where('nombre', 'Disponible')->value('id'),
            'codigo_interno' => 'EQ-' . uniqid(), 'activo' => true,
        ]);

        return EquipoAtributoGrupoInstancia::create([
            'equipo_id' => $equipo->id, 'atributo_id' => $atributo->id, 'valores' => $valores,
            'orden' => 0, 'es_actual' => true, 'creado_por' => $this->admin->id,
        ]);
    }

    /** Clave de la posición de un valor viejo en la lista de equivalencias del campo Tipo. */
    private function posicion($modal, string $valorViejo): int
    {
        return array_search($valorViejo, $modal->instance()->porEquivaler[$this->campo('Tipo')]['valores'], true);
    }

    // ─── Vinculación con equivalencias ─────────────────────────────────────

    /** @test */
    public function el_asistente_sugiere_equivalencias_y_convierte_los_discos_de_desktop(): void
    {
        $hdd  = $this->ponerDisco($this->discoDesktop, [self::CANTIDAD_DSK => '500', self::TIPO_DSK => 'HDD 3.5']);
        $ssd  = $this->ponerDisco($this->discoDesktop, [self::CANTIDAD_DSK => '256', self::TIPO_DSK => 'SSD Sata']);
        $nvme = $this->ponerDisco($this->discoDesktop, [self::CANTIDAD_DSK => '512', self::TIPO_DSK => 'M.2 Nvme']);

        $modal = Livewire::actingAs($this->admin)
            ->test(VincularFamiliaModal::class)
            ->call('abrir', $this->discoDesktop->id)
            ->set('familiaId', $this->discos->id)
            // "Cantidad" no se parece a "Capacidad (GB)": se elige a mano.
            ->set('mapeo.' . $this->campo('Capacidad (GB)') . '.fuente', 'sub:' . self::CANTIDAD_DSK)
            ->assertSee('Estos valores no están en la lista');

        $tipo = $this->campo('Tipo');
        $modal->assertSet("equivalencias.{$tipo}." . $this->posicion($modal, 'HDD 3.5'), 'HDD 3.5"')
              ->assertSet("equivalencias.{$tipo}." . $this->posicion($modal, 'SSD Sata'), 'SSD 2.5" SATA')
              ->assertSet("equivalencias.{$tipo}." . $this->posicion($modal, 'M.2 Nvme'), 'M.2 NVMe')
              ->call('previsualizar')
              ->assertHasNoErrors()
              ->assertSee('M.2 NVMe')
              ->call('confirmar')
              ->assertHasNoErrors();

        $this->assertSame($this->discos->id, $this->discoDesktop->fresh()->familia_id);
        $this->assertSame([$this->campo('Capacidad (GB)') => '500', $tipo => 'HDD 3.5"'], $hdd->fresh()->valores);
        $this->assertSame('SSD 2.5" SATA', $ssd->fresh()->valores[$tipo]);
        $this->assertSame('M.2 NVMe', $nvme->fresh()->valores[$tipo]);
    }

    /** @test */
    public function un_valor_ambiguo_no_se_sugiere_y_se_elige_a_mano(): void
    {
        $instancia = $this->ponerDisco($this->discoLaptop, [self::CAPACIDAD_LAP => '500', self::TIPO_LAP => 'HDD']);
        $this->ponerDisco($this->discoLaptop, [self::CAPACIDAD_LAP => '512', self::TIPO_LAP => 'NVME']);

        $modal = Livewire::actingAs($this->admin)
            ->test(VincularFamiliaModal::class)
            ->call('abrir', $this->discoLaptop->id)
            ->set('familiaId', $this->discos->id);

        $tipo = $this->campo('Tipo');
        $iHdd = $this->posicion($modal, 'HDD');
        $modal->assertSet("equivalencias.{$tipo}.{$iHdd}", '')           // "HDD" puede ser 2.5" o 3.5"
              ->assertSet("equivalencias.{$tipo}." . $this->posicion($modal, 'NVME'), 'M.2 NVMe')
              ->set("equivalencias.{$tipo}.{$iHdd}", 'HDD 2.5"')
              ->call('previsualizar')
              ->call('confirmar')
              ->assertHasNoErrors();

        $this->assertSame('HDD 2.5"', $instancia->fresh()->valores[$tipo]);
    }

    /** @test */
    public function una_equivalencia_solo_puede_llevar_a_una_opcion_de_la_lista(): void
    {
        $this->ponerDisco($this->discoLaptop, [self::CAPACIDAD_LAP => '512', self::TIPO_LAP => 'NVME']);

        $this->expectException(\InvalidArgumentException::class);
        app(VinculacionFamiliaService::class)->vincular($this->discoLaptop, $this->discos, [
            $this->campo('Capacidad (GB)') => ['origen' => 'sub_campo', 'sub_campo_id' => self::CAPACIDAD_LAP],
            $this->campo('Tipo')           => ['origen' => 'sub_campo', 'sub_campo_id' => self::TIPO_LAP, 'equivalencias' => ['NVME' => 'Cualquier cosa']],
        ]);
    }

    // ─── Datos de ejemplo ──────────────────────────────────────────────────

    private function vincularSinDatos(): void
    {
        $ram = FamiliaComponente::where('nombre', 'RAM')->firstOrFail();
        $ramLaptop = AtributoEquipo::create(['categoria_id' => $this->discoLaptop->categoria_id, 'nombre' => 'RAM', 'slug' => 'ram', 'tipo' => 'group', 'reutilizable' => true]);
        app(VinculacionFamiliaService::class)->vincularSinDatos($ramLaptop, $ram);
        app(VinculacionFamiliaService::class)->vincularSinDatos($this->discoLaptop, $this->discos);
    }

    /** @test */
    public function el_comando_de_ejemplos_simula_por_defecto(): void
    {
        $this->vincularSinDatos();

        $this->artisan('cerberus:almacen-ejemplos', ['--empresa' => $this->empresa->id, '--usuario' => $this->admin->id])
            ->assertSuccessful();

        $this->assertSame(0, ComponenteAlmacen::count());
    }

    /** @test */
    public function el_comando_carga_ejemplos_de_cada_tipo_y_los_quita(): void
    {
        $this->vincularSinDatos();
        $opciones = ['--empresa' => $this->empresa->id, '--usuario' => $this->admin->id, '--ejecutar' => true];

        $this->artisan('cerberus:almacen-ejemplos', $opciones)->assertSuccessful();

        $ssd = ComponenteAlmacen::where('familia_id', $this->discos->id)->where('nombre', 'like', '%SSD 2.5%')->firstOrFail();
        $this->assertSame(3, $ssd->stock_actual);
        $this->assertSame(['EJEMPLO-SSD256-001', 'EJEMPLO-SSD256-002', 'EJEMPLO-SSD256-003'], $ssd->piezasExtraidas()->orderBy('id')->pluck('identificador')->all());
        $this->assertNotNull($ssd->piezasExtraidas()->first()->atributo_id, 'Ligadas a la ficha: se pueden instalar.');
        $this->assertSame(100, ComponenteAlmacen::where('nombre', 'Tornillos M2 x 3 mm')->value('stock_actual'));
        $this->assertTrue(FamiliaComponente::where('nombre', 'Bisagras')->firstOrFail()->esAGranel());

        // No se cargan dos veces.
        $this->artisan('cerberus:almacen-ejemplos', $opciones)->assertFailed();

        // Una pieza de ejemplo que se usó en un caso hace que su variante se quede.
        $pantalla = PiezaExtraida::where('identificador', 'EJEMPLO-PAN14-001')->firstOrFail();
        $equipo = Equipo::forceCreate([
            'empresa_id' => $this->empresa->id, 'categoria_id' => $this->discoLaptop->categoria_id,
            'estado_id' => EstadoEquipo::where('nombre', 'En reparación')->value('id'),
            'codigo_interno' => 'EQ-USO', 'activo' => true,
        ]);
        $caso = Mantenimiento::create([
            'empresa_id' => $this->empresa->id, 'equipo_id' => $equipo->id, 'tipo' => 'Correctivo',
            'estado' => 'En reparación', 'reportado_por_id' => $this->admin->id, 'fecha_inicio' => now()->toDateString(),
        ]);
        $caso->usarPieza($pantalla, $this->admin);

        $this->artisan('cerberus:almacen-ejemplos', $opciones + ['--quitar' => true])->assertSuccessful();

        $this->assertSame(['Pantalla 14" HD 30 pines'], ComponenteAlmacen::pluck('nombre')->all());
        $this->assertSame(0, PiezaExtraida::where('identificador', 'like', 'EJEMPLO-SSD%')->count());
        $this->assertNull(FamiliaComponente::where('nombre', 'Bisagras')->first(), 'La familia que creó el comando se borra al quedar vacía.');
        $this->assertNotNull(FamiliaComponente::where('nombre', 'Pantallas')->first(), 'Esta todavía tiene la variante usada.');
    }
}
