<?php

namespace Tests\Feature\Configuracion;

use App\Livewire\Configuracion\Atributos\AtributosEditorModal;
use App\Livewire\Configuracion\Atributos\VincularFamiliaModal;
use App\Livewire\Configuracion\Familias\FamiliaModal;
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
use App\Services\ExtraccionPiezaService;
use App\Services\SustitucionPiezaService;
use App\Services\VinculacionFamiliaService;
use Database\Seeders\EstadosEquiposSeeder;
use Database\Seeders\FamiliasComponentesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Fase 1 — Familias de piezas con ficha técnica compartida (RAM).
 */
class FamiliasComponentesTest extends TestCase
{
    use RefreshDatabase;

    private const CANTIDAD = 'aaaa1111-0000-0000-0000-000000000001';
    private const TIPO_DSK = 'aaaa1111-0000-0000-0000-000000000002';
    private const CAPACIDAD = 'bbbb2222-0000-0000-0000-000000000001';
    private const TIPO_LAP = 'bbbb2222-0000-0000-0000-000000000002';

    private User $admin;
    private Empresa $empresa;
    private CategoriaEquipo $desktop;
    private CategoriaEquipo $laptop;
    private FamiliaComponente $ram;
    private AtributoEquipo $ramDesktop;
    private AtributoEquipo $ramLaptop;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Analista', 'Usuario'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->seed(EstadosEquiposSeeder::class);
        $this->seed(FamiliasComponentesSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->empresa = Empresa::create(['nombre' => 'Empresa A']);
        $this->desktop = CategoriaEquipo::create(['nombre' => 'Desktop']);
        $this->laptop  = CategoriaEquipo::create(['nombre' => 'Laptop']);
        $this->ram     = FamiliaComponente::where('nombre', 'RAM')->firstOrFail();

        // Como en producción: cada categoría con su propia RAM y nombres distintos.
        $this->ramDesktop = AtributoEquipo::create([
            'categoria_id' => $this->desktop->id, 'nombre' => 'RAM (GB)', 'slug' => 'ram-gb', 'tipo' => 'group', 'reutilizable' => true,
            'sub_campos'   => [
                ['id' => self::CANTIDAD, 'nombre' => 'Cantidad', 'tipo' => 'string', 'opciones' => [], 'requerido' => false],
                ['id' => self::TIPO_DSK, 'nombre' => 'Tipo', 'tipo' => 'select', 'opciones' => ['DDR3', 'DDR4'], 'requerido' => false],
            ],
        ]);
        $this->ramLaptop = AtributoEquipo::create([
            'categoria_id' => $this->laptop->id, 'nombre' => 'RAM', 'slug' => 'ram', 'tipo' => 'group', 'reutilizable' => true,
            'sub_campos'   => [
                ['id' => self::CAPACIDAD, 'nombre' => 'Capacidad (GB)', 'tipo' => 'integer', 'opciones' => [], 'requerido' => false],
                ['id' => self::TIPO_LAP, 'nombre' => 'Tipo', 'tipo' => 'select', 'opciones' => ['DDR3', 'DDR4'], 'requerido' => false],
            ],
        ]);
    }

    // ─── Fixtures ──────────────────────────────────────────────────────────

    private function campo(string $nombre): string
    {
        return collect($this->ram->campos)->firstWhere('nombre', $nombre)['id'];
    }

    private function crearEquipo(CategoriaEquipo $categoria): Equipo
    {
        return Equipo::forceCreate([
            'empresa_id'     => $this->empresa->id,
            'categoria_id'   => $categoria->id,
            'estado_id'      => EstadoEquipo::where('nombre', 'Disponible')->value('id'),
            'codigo_interno' => 'EQ-' . uniqid(),
            'activo'         => true,
        ]);
    }

    private function ponerRam(Equipo $equipo, AtributoEquipo $atributo, array $valores): EquipoAtributoGrupoInstancia
    {
        return EquipoAtributoGrupoInstancia::create([
            'equipo_id' => $equipo->id, 'atributo_id' => $atributo->id, 'valores' => $valores,
            'orden' => 0, 'es_actual' => true, 'creado_por' => $this->admin->id,
        ]);
    }

    private function mapeoDesktop(): array
    {
        return [
            $this->campo('Capacidad (GB)') => ['origen' => 'sub_campo', 'sub_campo_id' => self::CANTIDAD],
            $this->campo('Tipo')           => ['origen' => 'sub_campo', 'sub_campo_id' => self::TIPO_DSK],
            $this->campo('Formato')        => ['origen' => 'fijo', 'valor' => 'DIMM (escritorio)'],
        ];
    }

    // ─── Ficha compartida ──────────────────────────────────────────────────

    /** @test */
    public function una_caracteristica_con_familia_toma_los_campos_de_la_familia(): void
    {
        app(VinculacionFamiliaService::class)->vincularSinDatos($this->ramLaptop, $this->ram);

        $this->assertSame(['Capacidad (GB)', 'Tipo', 'Formato'], array_column($this->ramLaptop->fresh()->sub_campos, 'nombre'));
        $this->assertSame(
            'RAM (Capacidad (GB): 8 / Tipo: DDR4)',
            $this->ramLaptop->fresh()->describirValor([$this->campo('Capacidad (GB)') => '8', $this->campo('Tipo') => 'DDR4']),
        );
    }

    /** @test */
    public function vincular_convierte_los_valores_de_equipos_y_piezas(): void
    {
        $equipo = $this->crearEquipo($this->desktop);
        $instancia = $this->ponerRam($equipo, $this->ramDesktop, [self::CANTIDAD => '8', self::TIPO_DSK => 'DDR4']);
        $pieza = PiezaExtraida::create([
            'empresa_id' => $this->empresa->id, 'equipo_origen_id' => $equipo->id, 'atributo_id' => $this->ramDesktop->id,
            'valor_extraido' => [self::CANTIDAD => '4', self::TIPO_DSK => 'DDR3'], 'reutilizable' => true,
            'estado' => 'en_almacen', 'motivo' => 'desarme_manual', 'extraido_por' => $this->admin->id,
        ]);

        $resultado = app(VinculacionFamiliaService::class)->vincular($this->ramDesktop, $this->ram, $this->mapeoDesktop());

        $this->assertSame(['instancias' => 1, 'piezas' => 1, 'componentes' => 0], $resultado);
        $this->assertSame([
            $this->campo('Capacidad (GB)') => '8', $this->campo('Tipo') => 'DDR4', $this->campo('Formato') => 'DIMM (escritorio)',
        ], $instancia->fresh()->valores);
        $this->assertSame('4', $pieza->fresh()->valor_extraido[$this->campo('Capacidad (GB)')]);
        $this->assertSame($this->ram->id, $this->ramDesktop->fresh()->familia_id);
    }

    /** @test */
    public function no_se_puede_dejar_un_sub_campo_con_datos_sin_destino(): void
    {
        $this->ponerRam($this->crearEquipo($this->desktop), $this->ramDesktop, [self::CANTIDAD => '8', self::TIPO_DSK => 'DDR4']);

        $mapeo = $this->mapeoDesktop();
        $mapeo[$this->campo('Capacidad (GB)')] = ['origen' => 'vacio'];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cantidad');
        app(VinculacionFamiliaService::class)->vincular($this->ramDesktop, $this->ram, $mapeo);
    }

    /** @test */
    public function desvincular_conserva_los_datos_validos(): void
    {
        $servicio = app(VinculacionFamiliaService::class);
        $instancia = $this->ponerRam($this->crearEquipo($this->desktop), $this->ramDesktop, [self::CANTIDAD => '8', self::TIPO_DSK => 'DDR4']);
        $servicio->vincular($this->ramDesktop, $this->ram, $this->mapeoDesktop());

        $servicio->desvincular($this->ramDesktop->fresh());

        $atributo = $this->ramDesktop->fresh();
        $this->assertNull($atributo->familia_id);
        $this->assertSame(['Capacidad (GB)', 'Tipo', 'Formato'], array_column($atributo->sub_campos, 'nombre'));
        $this->assertSame('8', $instancia->fresh()->valores[$this->campo('Capacidad (GB)')]);
    }

    // ─── Piezas entre categorías ───────────────────────────────────────────

    /** @test */
    public function una_ram_de_desktop_se_instala_en_la_ram_de_una_laptop(): void
    {
        $servicio = app(VinculacionFamiliaService::class);
        $servicio->vincularSinDatos($this->ramDesktop, $this->ram);
        $servicio->vincularSinDatos($this->ramLaptop, $this->ram);

        $pieza = app(ExtraccionPiezaService::class)->registrarNueva(
            $this->empresa->id, $this->ramDesktop->fresh(),
            [$this->campo('Capacidad (GB)') => '8', $this->campo('Tipo') => 'DDR4', $this->campo('Formato') => 'SO-DIMM (laptop)'],
            $this->admin,
        )->first();

        $laptop = $this->crearEquipo($this->laptop);
        $caso = Mantenimiento::create([
            'empresa_id' => $this->empresa->id, 'equipo_id' => $laptop->id, 'tipo' => 'Correctivo',
            'estado' => 'En reparación', 'reportado_por_id' => $this->admin->id, 'fecha_inicio' => now()->toDateString(),
        ]);

        $sustitucion = app(SustitucionPiezaService::class);
        $this->assertTrue($sustitucion->piezasDisponibles($laptop)->contains('id', $pieza->id));

        $sustitucion->instalar($pieza->fresh(), $laptop, SustitucionPiezaService::MODO_AGREGAR, $this->admin, $caso);

        $this->assertDatabaseHas('equipo_atributo_grupo_instancias', [
            'equipo_id' => $laptop->id, 'atributo_id' => $this->ramLaptop->id, 'es_actual' => true,
        ]);
        $this->assertSame(PiezaExtraida::ESTADO_INSTALADA, $pieza->fresh()->estado);
    }

    /** @test */
    public function el_mismo_valor_de_ram_cae_en_un_solo_componente_sin_importar_la_categoria(): void
    {
        $servicio = app(VinculacionFamiliaService::class);
        $servicio->vincularSinDatos($this->ramDesktop, $this->ram);
        $servicio->vincularSinDatos($this->ramLaptop, $this->ram);

        $valor = [$this->campo('Capacidad (GB)') => '8', $this->campo('Tipo') => 'DDR4', $this->campo('Formato') => 'SO-DIMM (laptop)'];
        $extraccion = app(ExtraccionPiezaService::class);
        $a = $extraccion->registrarNueva($this->empresa->id, $this->ramDesktop->fresh(), $valor, $this->admin)->first();
        $b = $extraccion->registrarNueva($this->empresa->id, $this->ramLaptop->fresh(), $valor, $this->admin)->first();

        $this->assertSame($a->componente_almacen_id, $b->componente_almacen_id);
        $this->assertSame(2, ComponenteAlmacen::find($a->componente_almacen_id)->stock_actual);
    }

    // ─── Pantallas de equipos con una característica de familia ───────────

    /** @test */
    public function las_pantallas_de_equipos_funcionan_con_la_ram_vinculada(): void
    {
        $instancia = $this->ponerRam($equipo = $this->crearEquipo($this->desktop), $this->ramDesktop, [self::CANTIDAD => '8', self::TIPO_DSK => 'DDR4']);
        app(VinculacionFamiliaService::class)->vincular($this->ramDesktop, $this->ram, $this->mapeoDesktop());
        $this->ramDesktop->update(['visible_en_tabla' => true, 'filtrable' => true]);

        $this->actingAs($this->admin);

        // Las columnas de características aparecen al filtrar por categoría.
        Livewire::test(\App\Livewire\Equipos\EquiposTable::class)
            ->set('categoria_id', (string) $this->desktop->id)
            ->assertSee('RAM (GB) - Formato')
            ->assertSee('DIMM (escritorio)');
        Livewire::test(\App\Livewire\Equipos\EquipoViewModal::class)->call('openEquipoView', $equipo->id)->assertSee('Formato');
        Livewire::test(\App\Livewire\Equipos\EditarEquipo::class, ['equipo' => $equipo])->assertSee('Capacidad (GB)');
        Livewire::test(\App\Livewire\Equipos\HistorialEquipo::class, ['equipo' => $equipo])->assertOk();
        Livewire::test(\App\Livewire\Equipos\CrearEquipo::class)->assertOk();

        $this->assertSame('DDR4', $instancia->fresh()->valores[$this->campo('Tipo')]);
    }

    // ─── Pantallas ─────────────────────────────────────────────────────────

    /** @test */
    public function solo_el_administrador_entra_a_familias(): void
    {
        $this->actingAs($this->admin)->get(route('admin.configuracion.familias'))->assertOk();

        $analista = User::factory()->create(['empresa_activa_id' => $this->empresa->id]);
        $analista->assignRole('Analista');
        $analista->empresasAsignadas()->attach($this->empresa->id);
        $this->actingAs($analista)->get(route('admin.configuracion.familias'))->assertForbidden();
    }

    /** @test */
    public function el_administrador_crea_una_familia_con_su_ficha(): void
    {
        Livewire::actingAs($this->admin)
            ->test(FamiliaModal::class)
            ->call('abrirCrear')
            ->call('agregarCampo')
            ->set('nombre', 'Discos')
            ->set('campos.0.nombre', 'Tecnología')
            ->set('campos.0.tipo', 'select')
            ->set('campos.0.opciones_raw', "HDD\nSSD\nNVMe")
            ->call('guardar')
            ->assertHasNoErrors();

        $discos = FamiliaComponente::where('nombre', 'Discos')->firstOrFail();
        $this->assertSame(['HDD', 'SSD', 'NVMe'], $discos->campos[0]['opciones']);
    }

    /** @test */
    public function no_se_quita_un_campo_de_la_familia_que_ya_tiene_valores(): void
    {
        app(VinculacionFamiliaService::class)->vincularSinDatos($this->ramLaptop, $this->ram);
        $this->ponerRam($this->crearEquipo($this->laptop), $this->ramLaptop->fresh(), [$this->campo('Tipo') => 'DDR4']);

        $indiceTipo = collect($this->ram->campos)->search(fn ($c) => $c['nombre'] === 'Tipo');

        Livewire::actingAs($this->admin)
            ->test(FamiliaModal::class)
            ->call('abrirEditar', $this->ram->id)
            ->call('quitarCampo', $indiceTipo)
            ->assertHasErrors("campos.{$indiceTipo}.nombre");

        $this->assertCount(3, $this->ram->fresh()->campos);
    }

    /** @test */
    public function el_editor_vincula_directo_una_caracteristica_sin_datos(): void
    {
        $componente = Livewire::actingAs($this->admin)
            ->test(AtributosEditorModal::class)
            ->call('abrir', $this->laptop->id);

        $indice = collect($componente->get('filas'))->search(fn ($f) => $f['id'] === $this->ramLaptop->id);

        $componente->set("filas.{$indice}.familia_id", (string) $this->ram->id)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame($this->ram->id, $this->ramLaptop->fresh()->familia_id);
    }

    /** @test */
    public function el_editor_no_vincula_directo_una_caracteristica_con_datos(): void
    {
        $this->ponerRam($this->crearEquipo($this->desktop), $this->ramDesktop, [self::CANTIDAD => '8']);

        $componente = Livewire::actingAs($this->admin)
            ->test(AtributosEditorModal::class)
            ->call('abrir', $this->desktop->id);

        $indice = collect($componente->get('filas'))->search(fn ($f) => $f['id'] === $this->ramDesktop->id);

        $componente->set("filas.{$indice}.familia_id", (string) $this->ram->id)
            ->call('guardar')
            ->assertHasErrors("filas.{$indice}.familia_id");

        $this->assertNull($this->ramDesktop->fresh()->familia_id);
    }

    /** @test */
    public function el_asistente_muestra_la_vista_previa_y_luego_convierte(): void
    {
        $instancia = $this->ponerRam($this->crearEquipo($this->desktop), $this->ramDesktop, [self::CANTIDAD => '8', self::TIPO_DSK => 'DDR4']);

        $componente = Livewire::actingAs($this->admin)
            ->test(VincularFamiliaModal::class)
            ->call('abrir', $this->ramDesktop->id)
            ->set('familiaId', $this->ram->id)
            ->set('mapeo.' . $this->campo('Capacidad (GB)') . '.fuente', 'sub:' . self::CANTIDAD)
            ->set('mapeo.' . $this->campo('Formato') . '.fuente', 'fijo')
            ->set('mapeo.' . $this->campo('Formato') . '.valor', 'DIMM (escritorio)')
            ->call('previsualizar')
            ->assertHasNoErrors();

        $this->assertSame(1, $componente->get('vistaPrevia')['instancias_vigentes']);
        $this->assertSame(self::CANTIDAD, array_key_first($instancia->fresh()->valores), 'La vista previa no debe guardar nada.');

        $componente->call('confirmar')->assertHasNoErrors();

        $this->assertSame('DIMM (escritorio)', $instancia->fresh()->valores[$this->campo('Formato')]);
        $this->assertSame($this->ram->id, $this->ramDesktop->fresh()->familia_id);
    }
}
